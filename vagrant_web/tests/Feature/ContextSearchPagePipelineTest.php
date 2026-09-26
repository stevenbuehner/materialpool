<?php

namespace Tests\Feature;

use App\Jobs\ContextSearch\EmbedContextSearchPage;
use App\Jobs\ContextSearch\CleanupContextSearchRevision;
use App\Jobs\ContextSearch\ExtractContextSearchPage;
use App\Jobs\IndexContextSearchResource;
use App\Models\ContextSearchIndexRun;
use App\Models\ContextSearchIndexRunPage;
use App\Models\ContextSearchIndexRunResource;
use App\Models\Text;
use App\Models\User;
use App\Services\ContextSearch\ContextSearchIndexPipeline;
use App\Services\ContextSearch\ContextSearchPageArtifactStore;
use App\Services\ContextSearch\ContextSearchPublicationGuard;
use App\Services\ContextSearch\ContextSearchResourceIndexer;
use App\Services\ContextSearch\ContextSearchSourceSnapshot;
use App\Services\ContextSearch\EmbeddingProfile;
use App\Services\ContextSearch\Extraction\ResourceTextExtractor;
use App\Services\ContextSearch\Ollama\OllamaEmbeddingPool;
use App\Services\ContextSearch\Ollama\OllamaServer;
use App\Services\ContextSearch\Qdrant\QdrantClient;
use App\Services\ContextSearch\TextChunker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ContextSearchPagePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_ocr_artifact_requeues_only_the_affected_page_for_extraction(): void
    {
        Storage::fake('local');
        Bus::fake([ExtractContextSearchPage::class]);
        $profile = $this->profile();
        $snapshot = new ContextSearchSourceSnapshot();
        [$resource, $run, $item] = $this->makeRun($profile, $snapshot);
        $page = ContextSearchIndexRunPage::query()->create([
            'run_resource_id' => $item->getKey(),
            'page_number' => 1,
            'status' => ContextSearchIndexRunPage::STATUS_EXTRACTED,
            'artifact_path' => (new ContextSearchPageArtifactStore())->path($run->getKey(), $resource->getKey(), $item->source_revision, 1),
            'artifact_hash' => str_repeat('a', 64),
        ]);
        $indexer = $this->indexer($profile, $this->qdrant());

        (new EmbedContextSearchPage($page->getKey()))->handle(app(ContextSearchIndexPipeline::class), $snapshot, $profile, new ContextSearchPageArtifactStore(), $indexer);

        $this->assertDatabaseHas('context_search_index_run_pages', ['id' => $page->getKey(), 'status' => ContextSearchIndexRunPage::STATUS_EXTRACTING, 'artifact_path' => null]);
        Bus::assertDispatched(ExtractContextSearchPage::class);
    }

    public function test_a_text_page_is_published_only_after_extraction_and_confirmed_upsert(): void
    {
        Storage::fake('local');
        Bus::fake([ExtractContextSearchPage::class, EmbedContextSearchPage::class]);
        Http::preventStrayRequests();
        Http::fake([
            'ollama.test/api/tags' => Http::response(['models' => [['name' => 'embeddinggemma:test', 'digest' => str_repeat('a', 64)]]]),
            'ollama.test/api/embed' => Http::response(['model' => 'embeddinggemma:test', 'embeddings' => [[0.1, 0.2, 0.3]]]),
        ]);
        $profile = $this->profile();
        $snapshot = new ContextSearchSourceSnapshot();
        [$resource, $run, $item] = $this->makeRun($profile, $snapshot);
        $pipeline = app(ContextSearchIndexPipeline::class);
        $item->update(['page_count' => null]);

        (new IndexContextSearchResource($run->getKey(), $resource->getKey()))->handle(app(ResourceTextExtractor::class), $snapshot, $profile, $pipeline);
        $page = ContextSearchIndexRunPage::query()->sole();
        $this->assertSame(ContextSearchIndexRunPage::STATUS_EXTRACTING, $page->status);

        (new ExtractContextSearchPage($page->getKey()))->handle($pipeline, $snapshot, $profile, app(ResourceTextExtractor::class), new ContextSearchPageArtifactStore());
        $this->assertSame(ContextSearchIndexRunPage::STATUS_EXTRACTED, $page->fresh()->status);

        $qdrant = $this->qdrant();
        (new EmbedContextSearchPage($page->getKey()))->handle($pipeline, $snapshot, $profile, new ContextSearchPageArtifactStore(), $this->indexer($profile, $qdrant));

        $this->assertSame(ContextSearchIndexRunPage::STATUS_INDEXED, $page->fresh()->status);
        $this->assertCount(1, $qdrant->upserts);
        $this->assertSame([], $qdrant->deleted);
        $this->assertDatabaseHas('context_search_resource_publications', [
            'collection_name' => $run->collection_name,
            'resource_id' => $resource->getKey(),
            'document_revision' => $item->source_revision,
            'index_revision' => $item->index_revision,
            'status' => 'published',
        ]);
        $this->assertSame(ContextSearchIndexRun::STATUS_COMPLETED, $run->fresh()->status);
        $this->assertSame($item->index_revision, (new ContextSearchPublicationGuard())->publishedRevision($resource, $run->collection_name, $profile->id()));
        config()->set('context_search.indexing.ocr_quality_profile', 'changed-quality-profile');
        $this->assertNull((new ContextSearchPublicationGuard())->publishedRevision($resource, $run->collection_name, $profile->id()));
        Bus::assertDispatched(ExtractContextSearchPage::class);
        Bus::assertDispatched(EmbedContextSearchPage::class);
    }

    public function test_publication_waits_for_every_page_and_rejects_a_changed_source(): void
    {
        Bus::fake([ExtractContextSearchPage::class]);
        $profile = $this->profile();
        $snapshot = new ContextSearchSourceSnapshot();
        [$resource, $run, $item] = $this->makeRun($profile, $snapshot);
        $item->update(['page_count' => 2]);
        $first = ContextSearchIndexRunPage::query()->create(['run_resource_id' => $item->getKey(), 'page_number' => 1, 'status' => ContextSearchIndexRunPage::STATUS_INDEXING]);
        $second = ContextSearchIndexRunPage::query()->create(['run_resource_id' => $item->getKey(), 'page_number' => 2, 'status' => ContextSearchIndexRunPage::STATUS_INDEXING]);
        $pipeline = app(ContextSearchIndexPipeline::class);

        $pipeline->finishPage($first->getKey(), ContextSearchIndexRunPage::STATUS_INDEXED, 1);
        $this->assertDatabaseCount('context_search_resource_publications', 0);

        $resource->setContent('Nach der Indexierung veränderter Text.');
        $resource->save();
        $pipeline->finishPage($second->getKey(), ContextSearchIndexRunPage::STATUS_INDEXED, 1);

        $this->assertDatabaseCount('context_search_resource_publications', 0);
        $this->assertSame(ContextSearchIndexRunResource::STATUS_FAILED, $item->fresh()->status);
        $this->assertSame(ContextSearchIndexRun::STATUS_FAILED, $run->fresh()->status);
    }

    public function test_revision_cleanup_only_deletes_a_superseded_published_revision(): void
    {
        $profile = $this->profile();
        $snapshot = new ContextSearchSourceSnapshot();
        [$resource, $run, $item] = $this->makeRun($profile, $snapshot);
        $old = str_repeat('b', 64);
        \App\Models\ContextSearchResourcePublication::query()->create([
            'collection_name' => $run->collection_name,
            'embedding_profile' => $profile->id(),
            'resource_id' => $resource->getKey(),
            'document_revision' => $item->source_revision,
            'index_revision' => $old,
            'status' => 'published',
        ]);
        $qdrant = $this->qdrant();

        (new CleanupContextSearchRevision($run->collection_name, $profile->id(), $resource->getKey(), $old, $item->index_revision))->handle($qdrant);
        $this->assertSame([], $qdrant->deleted);

        \App\Models\ContextSearchResourcePublication::query()->firstOrFail()->update(['index_revision' => $item->index_revision]);
        (new CleanupContextSearchRevision($run->collection_name, $profile->id(), $resource->getKey(), $old, $item->index_revision))->handle($qdrant);

        $this->assertSame([[
            'collection' => $run->collection_name,
            'resourceId' => $resource->getKey(),
            'embeddingProfile' => $profile->id(),
            'revision' => $old,
        ]], $qdrant->deleted);
    }

    public function test_planner_repairs_missing_page_rows_after_a_crash_before_dispatch(): void
    {
        Bus::fake([ExtractContextSearchPage::class]);
        $profile = $this->profile();
        $snapshot = new ContextSearchSourceSnapshot();
        [$resource, $run, $item] = $this->makeRun($profile, $snapshot);
        $item->update(['page_count' => 1]);

        (new IndexContextSearchResource($run->getKey(), $resource->getKey()))->handle(app(ResourceTextExtractor::class), $snapshot, $profile, app(ContextSearchIndexPipeline::class));

        $this->assertDatabaseHas('context_search_index_run_pages', [
            'run_resource_id' => $item->getKey(),
            'page_number' => 1,
            'status' => ContextSearchIndexRunPage::STATUS_EXTRACTING,
        ]);
        Bus::assertDispatched(ExtractContextSearchPage::class);
    }

    private function profile(): EmbeddingProfile
    {
        return new EmbeddingProfile('embeddinggemma:test', str_repeat('a', 64), 3, []);
    }

    /** @return array{Text, ContextSearchIndexRun, ContextSearchIndexRunResource} */
    private function makeRun(EmbeddingProfile $profile, ContextSearchSourceSnapshot $snapshot): array
    {
        $user = User::factory()->create();
        $resource = Text::factory()->create(['created_by' => $user->getKey()]);
        $resource->setContent('Ein eindeutig belegbarer kurzer Text für die Kontextsuche.');
        $resource->save();
        $run = ContextSearchIndexRun::query()->create([
            'status' => ContextSearchIndexRun::STATUS_RUNNING,
            'collection_name' => 'test_collection',
            'embedding_profile' => $profile->id(),
            'total_resources' => 1,
        ]);
        $item = ContextSearchIndexRunResource::query()->create([
            'run_id' => $run->getKey(),
            'resource_id' => $resource->getKey(),
            'status' => ContextSearchIndexRunResource::STATUS_RUNNING,
            'source_revision' => $snapshot->revision($resource),
            'index_revision' => $snapshot->indexRevision($snapshot->revision($resource), $profile->id()),
            'extraction_profile' => $snapshot->extractionProfile(),
            'chunking_profile' => $snapshot->chunkingProfile(),
            'page_count' => 1,
        ]);

        return [$resource, $run, $item];
    }

    private function indexer(EmbeddingProfile $profile, QdrantClient $qdrant): ContextSearchResourceIndexer
    {
        $pool = new OllamaEmbeddingPool([new OllamaServer('primary', 'http://ollama.test', null, 1)], $profile, Cache::store(), 2, 10, 2, 60);

        return new ContextSearchResourceIndexer(new TextChunker(1400, 0), $pool, $qdrant, 8);
    }

    private function qdrant(): QdrantClient
    {
        return new class implements QdrantClient {
            public array $upserts = [];
            public array $deleted = [];
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
    }
}
