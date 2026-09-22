<?php

namespace App\Services\ContextSearch;

use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
use App\Services\ContextSearch\Extraction\ResourceTextExtractor;
use App\Services\ContextSearch\Ollama\OllamaEmbeddingPool;
use App\Services\ContextSearch\Qdrant\QdrantClient;
use InvalidArgumentException;
use RuntimeException;

final class ContextSearchResourceIndexer
{
    public function __construct(
        private readonly ResourceTextExtractor $extractor,
        private readonly TextChunker $chunker,
        private readonly OllamaEmbeddingPool $embeddings,
        private readonly QdrantClient $qdrant,
        private readonly int $embeddingBatchSize,
    ) {
    }

    public function index(Resource $resource, string $collection): int
    {
        if ($this->embeddingBatchSize < 1) {
            throw new InvalidArgumentException('The context-search embedding batch size must be positive.');
        }

        $pages = $this->extractor->extract($resource);
        $revision = $this->documentRevision($resource);
        $points = [];

        foreach ($pages as $page) {
            foreach ($this->chunker->chunk($page->text) as $chunk) {
                $points[] = [
                    'id' => $this->pointId($collection, $resource->getKey(), $revision, $page->pageNumber, $chunk->ordinal),
                    'text' => $chunk->content,
                    'payload' => [
                        'resource_id' => (int) $resource->getKey(),
                        'document_revision' => $revision,
                        'source_type' => $resource instanceof PdfFile ? 'pdf' : 'text',
                        'page_number' => $page->pageNumber,
                        'chunk_ordinal' => $chunk->ordinal,
                        'start_character' => $chunk->startCharacter,
                        'end_character' => $chunk->endCharacter,
                        'chunk_text' => $chunk->content,
                        'language' => $this->detectLanguage($chunk->content),
                        'extraction_method' => $page->method,
                        'extraction_quality' => $page->quality,
                        'extractor_version' => $page->extractorVersion,
                        'chunking_version' => (string) config('context_search.chunking.version'),
                        'embedding_profile' => $this->embeddings->profile()->id(),
                        'indexed_at' => now()->toIso8601String(),
                    ],
                ];
            }
        }

        if ($points === []) {
            throw new RuntimeException('The resource did not contain indexable text.');
        }

        $this->qdrant->deleteResourcePoints($collection, (int) $resource->getKey(), $this->embeddings->profile()->id());

        foreach (array_chunk($points, $this->embeddingBatchSize) as $batch) {
            $response = $this->embeddings->embed(array_column($batch, 'text'), 'resource:'.$resource->getKey());
            $upserts = [];

            foreach ($batch as $position => $point) {
                $upserts[] = [
                    'id' => $point['id'],
                    'vector' => $response->embeddings[$position],
                    'payload' => $point['payload'],
                ];
            }

            $this->qdrant->upsertPoints($collection, $upserts);
        }

        return count($points);
    }

    private function documentRevision(Resource $resource): string
    {
        if ($resource instanceof Text) {
            return hash('sha256', (string) $resource->getContent());
        }

        if ($resource instanceof PdfFile) {
            $path = $resource->getAbsoluteLocalPath();

            if (is_string($path) && is_file($path)) {
                $hash = hash_file('sha256', $path);

                if ($hash !== false) {
                    return $hash;
                }
            }
        }

        return hash('sha256', (string) $resource->content_hash."\0".(string) $resource->getKey());
    }

    private function pointId(string $collection, int $resourceId, string $revision, int $pageNumber, int $ordinal): string
    {
        $hash = hash('sha256', implode(':', [
            $collection,
            $this->embeddings->profile()->id(),
            $resourceId,
            $revision,
            $pageNumber,
            $ordinal,
        ]));

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-5'.substr($hash, 13, 3).'-a'.substr($hash, 17, 3).'-'.substr($hash, 20, 12);
    }

    private function detectLanguage(string $text): string
    {
        $words = preg_split('/[^\pL]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $german = count(array_intersect($words, ['der', 'die', 'das', 'und', 'ist', 'nicht', 'mit', 'für', 'ein', 'eine']));
        $english = count(array_intersect($words, ['the', 'and', 'is', 'are', 'with', 'for', 'this', 'that', 'of', 'to']));

        return $german === $english ? 'und' : ($german > $english ? 'de' : 'en');
    }
}
