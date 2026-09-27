<?php

namespace App\Jobs\ContextSearch;

use App\Models\ContextSearchIndexRunPage;
use App\Models\ContextSearchIndexRunResource;
use App\Services\ContextSearch\ContextSearchIndexPipeline;
use App\Services\ContextSearch\ContextSearchPageArtifactStore;
use App\Services\ContextSearch\ContextSearchPageBudgetException;
use App\Services\ContextSearch\ContextSearchSourceSnapshot;
use App\Services\ContextSearch\EmbeddingProfile;
use App\Services\ContextSearch\Extraction\ResourceTextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class ExtractContextSearchPage implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 480;
	public $tries   = 3;
	public $backoff = [30, 120];

	public function __construct(private readonly int $pageId) {
		$this->onConnection((string)config('context_search.indexing.connection'));
		$this->onQueue((string)config('context_search.indexing.queue'));
	}

	public function handle(ContextSearchIndexPipeline $pipeline, ContextSearchSourceSnapshot $snapshot, EmbeddingProfile $profile, ResourceTextExtractor $extractor, ContextSearchPageArtifactStore $artifacts): void {
		$lock = Cache::lock('context-search:extract:' . $this->pageId, 570);
		if (!$lock->get()) {
			return;
		}

		try {
			$page = ContextSearchIndexRunPage::query()->find($this->pageId);
			if ($page === NULL || !in_array($page->status, [ContextSearchIndexRunPage::STATUS_EXTRACTING, ContextSearchIndexRunPage::STATUS_EXTRACTED], TRUE)) {
				return;
			}

			$resource = $pipeline->currentResource($page, $snapshot, $profile);
			if ($resource === NULL) {
				$pipeline->finishPage($this->pageId, ContextSearchIndexRunPage::STATUS_STALE, failureCode: 'source_or_profile_changed');
				return;
			}

			$item      = ContextSearchIndexRunResource::query()->findOrFail($page->run_resource_id);
			$extracted = $page->artifact_path !== NULL && $page->artifact_hash !== NULL
				? $artifacts->read($page->artifact_path, $page->artifact_hash, $page->page_number)
				: NULL;
			if ($extracted === NULL) {
				$extracted = $extractor->extractPage($resource, $page->page_number);
				if ($pipeline->currentResource($page, $snapshot, $profile) === NULL) {
					$pipeline->finishPage($this->pageId, ContextSearchIndexRunPage::STATUS_STALE, failureCode: 'source_changed_during_extraction');
					return;
				}

				try {
					$stored = $artifacts->write($item->run_id, $item->resource_id, $item->source_revision, $extracted);
				} catch (ContextSearchPageBudgetException) {
					$pipeline->finishPage($this->pageId, ContextSearchIndexRunPage::STATUS_FAILED, failureCode: 'artifact_too_large');
					return;
				}
				$page->update([
					'artifact_path'     => $stored['path'],
					'artifact_hash'     => $stored['hash'],
					'extraction_method' => $extracted->method,
					'skip_reasons'      => $extracted->qualityReasons,
				]);
			}

			$page->update([
				'status'       => ContextSearchIndexRunPage::STATUS_EXTRACTED,
				'failure_code' => NULL,
				'skip_reasons' => $extracted->qualityReasons,
			]);
			EmbedContextSearchPage::dispatch($this->pageId);
		} finally {
			$lock->release();
		}
	}

	public function failed(?Throwable $exception): void {
		app(ContextSearchIndexPipeline::class)->finishPage($this->pageId, ContextSearchIndexRunPage::STATUS_FAILED, failureCode: 'extraction_failed');
	}
}
