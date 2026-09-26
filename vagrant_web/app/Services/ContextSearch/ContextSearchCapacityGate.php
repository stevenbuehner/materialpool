<?php

namespace App\Services\ContextSearch;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ContextSearchCapacityGate
{
    public function allowsNewBackgroundJobs(int $additionalJobs = 1): bool
    {
        $freeBytes = disk_free_space(storage_path('app'));
        if ($freeBytes === false || $freeBytes < (int) config('context_search.indexing.minimum_free_disk_bytes')) {
            return false;
        }

        $queues = [
            (string) config('context_search.indexing.queue'),
            (string) config('context_search.indexing.ocr_calibration_queue'),
            (string) config('context_search.indexing.embedding_queue'),
            (string) config('context_search.indexing.upsert_queue'),
        ];

        return DB::table('jobs')->whereIn('queue', $queues)->count() + $additionalJobs
            <= (int) config('context_search.indexing.maximum_queued_jobs');
    }

    public function assertCanStart(int $additionalJobs = 1): void
    {
        if (! $this->allowsNewBackgroundJobs($additionalJobs)) {
            throw new RuntimeException('Neue Kontextsuche-Jobs bleiben wegen SSD- oder Queue-Kapazitätsgrenze angehalten.');
        }
    }
}
