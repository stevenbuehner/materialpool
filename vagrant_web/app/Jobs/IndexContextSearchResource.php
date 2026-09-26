<?php

namespace App\Jobs;

use App\Models\ContextSearchIndexRun;
use App\Models\ContextSearchIndexRunResource;
use App\Models\Resource;
use App\Services\ContextSearch\ContextSearchResourceIndexer;
use App\Services\ContextSearch\EmbeddingProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class IndexContextSearchResource implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;
    public $tries = 3;
    public $backoff = [30, 120];

    public function __construct(private readonly string $runId, private readonly int $resourceId)
    {
        $this->onConnection((string) config('context_search.indexing.connection'));
        $this->onQueue((string) config('context_search.indexing.queue'));
    }

    public function uniqueId(): string
    {
        return $this->runId.':'.$this->resourceId;
    }

    public function handle(ContextSearchResourceIndexer $indexer, EmbeddingProfile $profile): void
    {
        $run = ContextSearchIndexRun::query()->find($this->runId);

        if ($run === null || $run->status === ContextSearchIndexRun::STATUS_FAILED) {
            return;
        }

        if ($run->embedding_profile !== $profile->id()) {
            throw new \RuntimeException('The index run embedding profile no longer matches the active verified profile.');
        }

        $runResource = ContextSearchIndexRunResource::query()
            ->where('run_id', $run->getKey())
            ->where('resource_id', $this->resourceId)
            ->first();

        if ($runResource === null || in_array($runResource->status, [ContextSearchIndexRunResource::STATUS_COMPLETED, ContextSearchIndexRunResource::STATUS_SKIPPED_LOW_QUALITY], true)) {
            return;
        }

        $runResource->update(['status' => ContextSearchIndexRunResource::STATUS_RUNNING, 'failure_message' => null]);
        $resource = (new Resource())->newQueryWithoutScopes()->find($this->resourceId);

        if ($resource === null) {
            $this->finishResource($run, $runResource, false, 0, 'The requested resource no longer exists.');
            return;
        }

        $result = $indexer->index($resource, $run->collection_name);
        $this->finishResource($run, $runResource, true, $result->indexedChunks, null, $result->totalPages, $result->skippedPages, $result->skippedReasons);
    }

    public function failed(?Throwable $exception): void
    {
        $run = ContextSearchIndexRun::query()->find($this->runId);

        if ($run !== null) {
            $runResource = ContextSearchIndexRunResource::query()
                ->where('run_id', $run->getKey())
                ->where('resource_id', $this->resourceId)
                ->first();

            if ($runResource !== null) {
                $this->finishResource($run, $runResource, false, 0, 'A resource could not be indexed.');
            }
        }
    }

    /** @param array<string, int> $skipReasons */
    private function finishResource(ContextSearchIndexRun $run, ContextSearchIndexRunResource $runResource, bool $successful, int $chunks, ?string $failure = null, int $indexedPages = 0, int $skippedPages = 0, array $skipReasons = []): void
    {
        $runResource->update([
            'status' => $successful
                ? ($chunks === 0 && $skippedPages > 0 ? ContextSearchIndexRunResource::STATUS_SKIPPED_LOW_QUALITY : ContextSearchIndexRunResource::STATUS_COMPLETED)
                : ContextSearchIndexRunResource::STATUS_FAILED,
            'indexed_chunks' => $chunks,
            'indexed_pages' => max(0, $indexedPages - $skippedPages),
            'skipped_pages' => $skippedPages,
            'skip_reasons' => $skipReasons,
            'failure_message' => $failure,
        ]);

        $processed = ContextSearchIndexRunResource::query()->where('run_id', $run->getKey())
            ->whereIn('status', [ContextSearchIndexRunResource::STATUS_COMPLETED, ContextSearchIndexRunResource::STATUS_SKIPPED_LOW_QUALITY])->count();
        $failed = ContextSearchIndexRunResource::query()->where('run_id', $run->getKey())
            ->where('status', ContextSearchIndexRun::STATUS_FAILED)->count();
        $run->update(['processed_resources' => $processed, 'failed_resources' => $failed]);

        if ($processed + $failed < $run->total_resources) {
            return;
        }

        $run->status = $failed === 0
            ? ContextSearchIndexRun::STATUS_COMPLETED
            : ContextSearchIndexRun::STATUS_FAILED;
        $run->failure_message = $failure;
        $run->finished_at = now();
        $run->save();
    }
}
