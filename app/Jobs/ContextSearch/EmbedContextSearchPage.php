<?php

namespace App\Jobs\ContextSearch;

use App\Models\ContextSearchIndexRun;
use App\Models\ContextSearchIndexRunPage;
use App\Models\ContextSearchIndexRunResource;
use App\Services\ContextSearch\ContextSearchIndexPipeline;
use App\Services\ContextSearch\ContextSearchPageArtifactStore;
use App\Services\ContextSearch\ContextSearchPageBudgetException;
use App\Services\ContextSearch\ContextSearchResourceIndexer;
use App\Services\ContextSearch\ContextSearchSourceSnapshot;
use App\Services\ContextSearch\EmbeddingProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class EmbedContextSearchPage implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 480;
	public $tries   = 3;
	public $backoff = [30, 120];

	public function __construct(private readonly int $pageId) {
		$this->onConnection((string)config('context_search.indexing.connection'));
		$this->onQueue((string)config('context_search.indexing.embedding_queue'));
	}

	public function handle(ContextSearchIndexPipeline $pipeline, ContextSearchSourceSnapshot $snapshot, EmbeddingProfile $profile, ContextSearchPageArtifactStore $artifacts, ContextSearchResourceIndexer $indexer): void {
		$lock = Cache::lock('context-search:embed:' . $this->pageId, 570);
		if (!$lock->get()) {
			return;
		}

		try {
			$page = ContextSearchIndexRunPage::query()->find($this->pageId);
			if ($page === NULL || !in_array($page->status, [ContextSearchIndexRunPage::STATUS_EXTRACTED, ContextSearchIndexRunPage::STATUS_INDEXING], TRUE)) {
				return;
			}

			$resource = $pipeline->currentResource($page, $snapshot, $profile);
			if ($resource === NULL) {
				$pipeline->finishPage($this->pageId, ContextSearchIndexRunPage::STATUS_STALE, failureCode: 'source_or_profile_changed');
				return;
			}

			$extracted = $page->artifact_path !== NULL && $page->artifact_hash !== NULL
				? $artifacts->read($page->artifact_path, $page->artifact_hash, $page->page_number)
				: NULL;
			if ($extracted === NULL) {
				// The artifact is disposable cache data: regenerate only this page on the OCR worker.
				$page->update(['status' => ContextSearchIndexRunPage::STATUS_EXTRACTING, 'artifact_hash' => NULL, 'artifact_path' => NULL]);
				ExtractContextSearchPage::dispatch($this->pageId);
				return;
			}

			if (!$extracted->accepted || trim($extracted->text) === '') {
				$pipeline->finishPage($this->pageId, ContextSearchIndexRunPage::STATUS_SKIPPED, failureCode: $extracted->accepted ? 'empty_page' : 'low_quality');
				return;
			}

			$page->update(['status' => ContextSearchIndexRunPage::STATUS_INDEXING]);
			$item = ContextSearchIndexRunResource::query()->findOrFail($page->run_resource_id);
			$run  = ContextSearchIndexRun::query()->findOrFail($item->run_id);

			try {
				$chunks = $indexer->indexPage($resource, $extracted, $run->collection_name, $item->source_revision, $item->index_revision);
			} catch (ContextSearchPageBudgetException) {
				$pipeline->finishPage($this->pageId, ContextSearchIndexRunPage::STATUS_FAILED, failureCode: 'too_many_chunks');
				return;
			}
			if ($pipeline->currentResource($page, $snapshot, $profile) === NULL) {
				$pipeline->finishPage($this->pageId, ContextSearchIndexRunPage::STATUS_STALE, failureCode: 'source_changed_during_indexing');
				return;
			}

			$pipeline->finishPage($this->pageId, $chunks === 0 ? ContextSearchIndexRunPage::STATUS_SKIPPED : ContextSearchIndexRunPage::STATUS_INDEXED, $chunks, $chunks === 0 ? 'empty_chunks' : NULL);
		} finally {
			$lock->release();
		}
	}

	public function failed(?Throwable $exception): void {
		app(ContextSearchIndexPipeline::class)->finishPage($this->pageId, ContextSearchIndexRunPage::STATUS_FAILED, failureCode: 'embedding_failed');
	}
}
