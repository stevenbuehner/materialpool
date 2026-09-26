<?php

namespace App\Services\ContextSearch;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ContextSearchCapacityGate
{
    public function allowsNewBackgroundJobs(): bool
    {
        $freeBytes = disk_free_space(storage_path('app'));
        if ($freeBytes === false || $freeBytes < (int) config('context_search.indexing.minimum_free_disk_bytes')) {
            return false;
        }

        $queues = [
            (string) config('context_search.indexing.queue'),
            (string) config('context_search.indexing.embedding_queue'),
            (string) config('context_search.indexing.upsert_queue'),
        ];

        return DB::table('jobs')->whereIn('queue', $queues)->count() < (int) config('context_search.indexing.maximum_queued_jobs');
    }

    public function assertCanStart(): void
    {
        if (! $this->allowsNewBackgroundJobs()) {
            throw new RuntimeException('Kontextsuche-Indexierung bleibt wegen SSD- oder Queue-Kapazitätsgrenze angehalten.');
        }
    }
}
