<?php

namespace App\Jobs;

use App\Models\ContextSearchIndexRun;
use App\Models\ContextSearchIndexRunPage;
use App\Models\ContextSearchIndexRunResource;
use App\Models\Resource;
use App\Services\ContextSearch\ContextSearchIndexPipeline;
use App\Services\ContextSearch\ContextSearchSourceSnapshot;
use App\Services\ContextSearch\EmbeddingProfile;
use App\Services\ContextSearch\Extraction\ResourceTextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class IndexContextSearchResource implements ShouldQueue, ShouldBeUnique {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout   = 480;
	public $tries     = 3;
	public $backoff   = [30, 120];
	public $uniqueFor = 660;

	public function __construct(private readonly string $runId, private readonly int $resourceId) {
		$this->onConnection((string)config('context_search.indexing.connection'));
		$this->onQueue((string)config('context_search.indexing.queue'));
	}

	public function uniqueId(): string {
		return $this->runId . ':' . $this->resourceId;
	}

	public function handle(ResourceTextExtractor $extractor, ContextSearchSourceSnapshot $snapshot, EmbeddingProfile $profile, ContextSearchIndexPipeline $pipeline): void {
		$run  = ContextSearchIndexRun::query()->find($this->runId);
		$item = ContextSearchIndexRunResource::query()->where('run_id', $this->runId)
			->where('resource_id', $this->resourceId)->first();

		if ($run === NULL || $item === NULL || $run->status !== ContextSearchIndexRun::STATUS_RUNNING
			|| $item->status !== ContextSearchIndexRunResource::STATUS_RUNNING) {
			return;
		}

		if ($run->embedding_profile !== $profile->id()) {
			$pipeline->failResource((int)$item->getKey(), 'embedding_profile_changed');
			return;
		}

		$resource = (new Resource())->newQueryWithoutScopes()->find($this->resourceId);
		if ($resource === NULL) {
			$pipeline->failResource((int)$item->getKey(), 'source_missing');
			return;
		}

		$revision          = $snapshot->revision($resource);
		$extractionProfile = $snapshot->extractionProfile();
		$chunkingProfile   = $snapshot->chunkingProfile();
		$indexRevision     = $snapshot->indexRevision($revision, $profile->id());

		if ($item->source_revision !== NULL && ($item->source_revision !== $revision
				|| $item->index_revision !== $indexRevision
				|| $item->extraction_profile !== $extractionProfile || $item->chunking_profile !== $chunkingProfile)) {
			$pipeline->failResource((int)$item->getKey(), 'source_or_profile_changed');
			return;
		}

		$pageCount = $item->page_count ?? $extractor->pageCount($resource);
		if ($pageCount < 1 || $pageCount > 1000) {
			$pipeline->failResource((int)$item->getKey(), 'invalid_page_count');
			return;
		}

		if ($item->page_count === NULL) {
			$item->update([
				'source_revision'    => $revision,
				'index_revision'     => $indexRevision,
				'extraction_profile' => $extractionProfile,
				'chunking_profile'   => $chunkingProfile,
				'page_count'         => $pageCount,
			]);

		}

		// A crash after saving page_count but before creating every row is restartable.
		for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
			ContextSearchIndexRunPage::query()->firstOrCreate(
				['run_resource_id' => $item->getKey(), 'page_number' => $pageNumber],
				['status' => ContextSearchIndexRunPage::STATUS_PENDING],
			);
		}

		$pipeline->advanceResource((int)$item->getKey());
	}

	public function failed(?Throwable $exception): void {
		$item = ContextSearchIndexRunResource::query()->where('run_id', $this->runId)
			->where('resource_id', $this->resourceId)->first();
		if ($item !== NULL) {
			app(ContextSearchIndexPipeline::class)->failResource((int)$item->getKey(), 'planning_failed');
		}
	}
}
