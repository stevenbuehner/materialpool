<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ContextSearchQueueCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventories_old_jobs_without_changing_them(): void
    {
        config()->set('context_search.indexing.queue', 'context-search-extraction');
        config()->set('queue.connections.context_search.retry_after', 600);
        DB::table('jobs')->insert([
            ['queue' => 'context-search-indexing', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => time(), 'created_at' => time()],
            ['queue' => 'context-search-indexing', 'payload' => '{}', 'attempts' => 1, 'reserved_at' => time(), 'available_at' => time(), 'created_at' => time()],
            ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => time(), 'created_at' => time()],
        ]);
        DB::table('failed_jobs')->insert([
            'connection' => 'database', 'queue' => 'context-search-indexing', 'payload' => '{}', 'exception' => 'test',
        ]);

        $this->artisan('context-search:queue:check')
            ->expectsOutputToContain('1 wartend, 1 reserviert, 1 fehlgeschlagen')
            ->assertExitCode(0);

        $this->assertDatabaseCount('jobs', 3);
        $this->assertDatabaseCount('failed_jobs', 1);
    }
}
