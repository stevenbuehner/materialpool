<?php

namespace Tests\Unit\Services\ContextSearch;

use App\Models\Text;
use App\Services\ContextSearch\ContextSearchResourceIndexer;
use App\Services\ContextSearch\EmbeddingProfile;
use App\Services\ContextSearch\Extraction\OcrProcessor;
use App\Services\ContextSearch\Extraction\OcrQualityGate;
use App\Services\ContextSearch\Extraction\OcrResult;
use App\Services\ContextSearch\Extraction\ExtractedPage;
use App\Services\ContextSearch\Extraction\ResourceTextExtractor;
use App\Services\ContextSearch\Ollama\OllamaEmbeddingPool;
use App\Services\ContextSearch\Ollama\OllamaServer;
use App\Services\ContextSearch\Qdrant\QdrantClient;
use App\Services\ContextSearch\TextChunker;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\PdfHandlingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ContextSearchResourceIndexerTest extends TestCase
{
    public function test_upserts_a_page_with_deterministic_source_payloads_without_deleting_published_points(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'ollama.test/api/tags' => Http::response(['models' => [[
                'name' => 'embeddinggemma:test',
                'digest' => str_repeat('a', 64),
            ]]]),
            'ollama.test/api/embed' => Http::response([
                'model' => 'embeddinggemma:test',
                'embeddings' => [[0.1, 0.2, 0.3]],
            ]),
        ]);

        $profile = new EmbeddingProfile('embeddinggemma:test', str_repeat('a', 64), 3, []);
        $qdrant = new class implements QdrantClient {
            public array $deleted = [];
            public array $upserts = [];
            public function isReady(): bool { return true; }
            public function collection(string $name): ?array { return null; }
            public function createCollection(string $name, int $dimensions, string $distance, bool $vectorsOnDisk, bool $payloadOnDisk): void {}
            public function createPayloadIndex(string $collection, string $field, string $schema): void {}
            public function aliases(): array { return []; }
            public function replaceAlias(string $alias, string $collection): void {}
            public function upsertPoints(string $collection, array $points): void { $this->upserts[] = $points; }
            public function deleteResourcePoints(string $collection, int $resourceId, string $embeddingProfile): void { $this->deleted[] = compact('collection', 'resourceId', 'embeddingProfile'); }
            public function deleteResourceRevisionPoints(string $collection, int $resourceId, string $embeddingProfile, string $revision): void { $this->deleted[] = compact('collection', 'resourceId', 'embeddingProfile', 'revision'); }
        };
        $ocr = new class implements OcrProcessor {
            public function extractPage(string $pdfPath, int $pageNumber): OcrResult { throw new \RuntimeException('Unexpected OCR.'); }
        };
        $text = new Text();
        $text->setAttribute('id', 42);
        $text->setAttribute('content_hash', 'current-content-hash');
        $text->setContent('Die Kontextsuche liefert eine belegbare Fundstelle für ein Material.');
        $extractor = new ResourceTextExtractor(new PdfHandlingService(new FileHandlingService()), new FileHandlingService(), $ocr, 80, new OcrQualityGate(0, 1, 0, 1));
        $pool = new OllamaEmbeddingPool([new OllamaServer('primary', 'http://ollama.test', null, 1)], $profile, Cache::store(), 2, 10, 2, 60);
        $indexer = new ContextSearchResourceIndexer(new TextChunker(100, 0), $pool, $qdrant, 8);
        $page = new ExtractedPage(1, $text->getContent(), 'native', 1.0, 'text-resource-v1');
        $revision = hash('sha256', $text->getContent());

        $this->assertSame(1, $indexer->indexPage($text, $page, 'active_collection', $revision, str_repeat('b', 64)));
        $this->assertSame(1, $indexer->indexPage($text, $page, 'active_collection', $revision, str_repeat('b', 64)));

        $this->assertSame([], $qdrant->deleted);
        $this->assertCount(2, $qdrant->upserts);
        $first = $qdrant->upserts[0][0];
        $second = $qdrant->upserts[1][0];
        $this->assertSame($first['id'], $second['id']);
        $this->assertSame(42, $first['payload']['resource_id']);
        $this->assertSame(str_repeat('b', 64), $first['payload']['index_revision']);
        $this->assertSame(1, $first['payload']['page_number']);
        $this->assertSame('text', $first['payload']['source_type']);
        $this->assertSame('native', $first['payload']['extraction_method']);
        $this->assertSame('Die Kontextsuche liefert eine belegbare Fundstelle für ein Material.', $first['payload']['chunk_text']);
        $this->assertSame(0, $first['payload']['start_character']);
        $this->assertSame(mb_strlen($first['payload']['chunk_text']), $first['payload']['end_character']);
    }
}
