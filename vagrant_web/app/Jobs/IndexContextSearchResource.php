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
        $this->onConnection('database');
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

        if ($runResource === null || $runResource->status === ContextSearchIndexRun::STATUS_COMPLETED) {
            return;
        }

        $runResource->update(['status' => ContextSearchIndexRun::STATUS_RUNNING, 'failure_message' => null]);
        $resource = (new Resource())->newQueryWithoutScopes()->find($this->resourceId);

        if ($resource === null) {
            $this->finishResource($run, $runResource, false, 0, 'The requested resource no longer exists.');
            return;
        }

        $chunks = $indexer->index($resource, $run->collection_name);
        $this->finishResource($run, $runResource, true, $chunks);
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

    private function finishResource(ContextSearchIndexRun $run, ContextSearchIndexRunResource $runResource, bool $successful, int $chunks, ?string $failure = null): void
    {
        $runResource->update([
            'status' => $successful ? ContextSearchIndexRun::STATUS_COMPLETED : ContextSearchIndexRun::STATUS_FAILED,
            'indexed_chunks' => $chunks,
            'failure_message' => $failure,
        ]);

        $processed = ContextSearchIndexRunResource::query()->where('run_id', $run->getKey())
            ->where('status', ContextSearchIndexRun::STATUS_COMPLETED)->count();
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
