<?php

namespace Tests\Feature;

use App\Jobs\IndexContextSearchResource;
use App\Models\ContextSearchIndexRun;
use App\Models\ContextSearchIndexRunResource;
use App\Models\Text;
use App\Models\User;
use App\Services\ContextSearch\EmbeddingProfile;
use App\Services\ContextSearch\Qdrant\QdrantClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class ContextSearchIndexCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_manually_queues_a_single_text_resource_and_persists_the_run(): void
    {
        config()->set('context_search.enabled', true);
        config()->set('context_search.indexing.dispatch_enabled', true);
        config()->set('context_search.indexing.queue', 'context-search-extraction');
        config()->set('queue.connections.context_search.retry_after', 600);
        Bus::fake([IndexContextSearchResource::class]);
        $this->app->instance(QdrantClient::class, $this->qdrant());
        $this->app->instance(EmbeddingProfile::class, new EmbeddingProfile('embeddinggemma:test', str_repeat('a', 64), 3, []));

        $user = User::factory()->create();
        $resource = Text::factory()->create(['created_by' => $user->getKey()]);

        $this->artisan('context-search:index', ['resource' => $resource->getKey()])
            ->expectsOutputToContain('nur manuell gestartet')
            ->assertExitCode(0);

        $run = ContextSearchIndexRun::query()->sole();
        $this->assertSame(ContextSearchIndexRun::STATUS_RUNNING, $run->status);
        $this->assertSame(1, $run->total_resources);
        $this->assertDatabaseHas('context_search_index_run_resources', [
            'run_id' => $run->getKey(),
            'resource_id' => $resource->getKey(),
            'status' => ContextSearchIndexRunResource::STATUS_RUNNING,
        ]);
        Bus::assertDispatched(IndexContextSearchResource::class, fn (IndexContextSearchResource $job): bool => $job->connection === 'context_search' && $job->queue === 'context-search-extraction');
    }

    public function test_resumes_only_failed_resources_from_a_manual_run(): void
    {
        config()->set('context_search.enabled', true);
        config()->set('context_search.indexing.dispatch_enabled', true);
        config()->set('context_search.indexing.queue', 'context-search-extraction');
        config()->set('queue.connections.context_search.retry_after', 600);
        Bus::fake([IndexContextSearchResource::class]);
        $this->app->instance(QdrantClient::class, $this->qdrant());

        $run = ContextSearchIndexRun::query()->create([
            'status' => ContextSearchIndexRun::STATUS_FAILED,
            'collection_name' => 'materialpool_chunks_profile_generation',
            'embedding_profile' => 'profile',
            'total_resources' => 1,
        ]);
        ContextSearchIndexRunResource::query()->create([
            'run_id' => $run->getKey(),
            'resource_id' => 123,
            'status' => ContextSearchIndexRun::STATUS_FAILED,
        ]);

        $this->artisan('context-search:index', ['--resume' => $run->getKey()])->assertExitCode(0);

        $this->assertDatabaseHas('context_search_index_runs', ['id' => $run->getKey(), 'status' => ContextSearchIndexRun::STATUS_RUNNING]);
        $this->assertDatabaseHas('context_search_index_run_resources', ['run_id' => $run->getKey(), 'resource_id' => 123, 'status' => ContextSearchIndexRunResource::STATUS_RUNNING]);
        Bus::assertDispatched(IndexContextSearchResource::class);
    }

    public function test_refuses_a_new_run_until_the_bounded_queue_pipeline_is_approved(): void
    {
        config()->set('context_search.enabled', true);
        Bus::fake([IndexContextSearchResource::class]);
        $this->app->instance(QdrantClient::class, $this->qdrant());

        $this->artisan('context-search:index', ['resource' => 123])
            ->expectsOutputToContain('bleiben bis zur Abnahme')
            ->assertExitCode(1);

        $this->assertDatabaseCount('context_search_index_runs', 0);
        Bus::assertNotDispatched(IndexContextSearchResource::class);
    }

    public function test_refuses_a_new_run_when_the_disk_safety_reserve_is_unavailable(): void
    {
        config()->set('context_search.enabled', true);
        config()->set('context_search.indexing.dispatch_enabled', true);
        config()->set('context_search.indexing.minimum_free_disk_bytes', PHP_INT_MAX);
        Bus::fake([IndexContextSearchResource::class]);
        $this->app->instance(QdrantClient::class, $this->qdrant());

        $this->artisan('context-search:index', ['resource' => 123])
            ->expectsOutputToContain('Kapazitätsgrenze')
            ->assertExitCode(1);

        $this->assertDatabaseCount('context_search_index_runs', 0);
        Bus::assertNotDispatched(IndexContextSearchResource::class);
    }

    private function qdrant(): QdrantClient
    {
        return new class implements QdrantClient {
            public function isReady(): bool { return true; }
            public function collection(string $name): ?array { return null; }
            public function createCollection(string $name, int $dimensions, string $distance, bool $vectorsOnDisk, bool $payloadOnDisk): void {}
            public function createPayloadIndex(string $collection, string $field, string $schema): void {}
            public function aliases(): array { return ['materialpool_chunks_active' => 'materialpool_chunks_profile_generation']; }
            public function replaceAlias(string $alias, string $collection): void {}
            public function upsertPoints(string $collection, array $points): void {}
            public function deleteResourcePoints(string $collection, int $resourceId, string $embeddingProfile): void {}
            public function deleteResourceRevisionPoints(string $collection, int $resourceId, string $embeddingProfile, string $revision): void {}
        };
    }
}
