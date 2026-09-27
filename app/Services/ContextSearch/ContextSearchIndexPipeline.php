<?php

namespace App\Services\ContextSearch;

use App\Jobs\ContextSearch\CleanupContextSearchRevision;
use App\Jobs\ContextSearch\EmbedContextSearchPage;
use App\Jobs\ContextSearch\ExtractContextSearchPage;
use App\Jobs\IndexContextSearchResource;
use App\Models\ContextSearchIndexRun;
use App\Models\ContextSearchIndexRunPage;
use App\Models\ContextSearchIndexRunResource;
use App\Models\ContextSearchResourcePublication;
use App\Models\Resource;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ContextSearchIndexPipeline {
	public function __construct(private readonly ContextSearchCapacityGate $capacity) {
	}

	public function requeueResource(int $runResourceId): void {
		if (!$this->capacity->allowsNewBackgroundJobs()) {
			return;
		}
		$item = ContextSearchIndexRunResource::query()->find($runResourceId);
		if ($item === NULL || $item->status !== ContextSearchIndexRunResource::STATUS_RUNNING) {
			return;
		}

		if ($item->page_count === NULL || ContextSearchIndexRunPage::query()->where('run_resource_id', $runResourceId)->count() !== $item->page_count) {
			IndexContextSearchResource::dispatch($item->run_id, $item->resource_id);
			return;
		}

		ContextSearchIndexRunPage::query()->where('run_resource_id', $runResourceId)
			->whereIn('status', [ContextSearchIndexRunPage::STATUS_EXTRACTING, ContextSearchIndexRunPage::STATUS_EXTRACTED, ContextSearchIndexRunPage::STATUS_INDEXING])
			->orderBy('page_number')->get()->each(function (ContextSearchIndexRunPage $page): void {
				if ($page->status === ContextSearchIndexRunPage::STATUS_EXTRACTING) {
					ExtractContextSearchPage::dispatch((int)$page->getKey());
				} else {
					EmbedContextSearchPage::dispatch((int)$page->getKey());
				}
			});

		$this->advanceResource($runResourceId);
	}

	public function advanceResource(int $runResourceId): void {
		$result = DB::transaction(function () use ($runResourceId): array {
			$item = ContextSearchIndexRunResource::query()->lockForUpdate()->find($runResourceId);
			if ($item === NULL || $item->status !== ContextSearchIndexRunResource::STATUS_RUNNING || $item->page_count === NULL) {
				return [[], NULL];
			}

			$active  = ContextSearchIndexRunPage::query()->where('run_resource_id', $item->getKey())
				->whereIn('status', [ContextSearchIndexRunPage::STATUS_EXTRACTING, ContextSearchIndexRunPage::STATUS_EXTRACTED, ContextSearchIndexRunPage::STATUS_INDEXING])->count();
			$limit   = $this->capacity->allowsNewBackgroundJobs()
				? max(0, (int)config('context_search.indexing.page_window', 4) - $active)
				: 0;
			$pending = ContextSearchIndexRunPage::query()->where('run_resource_id', $item->getKey())
				->where('status', ContextSearchIndexRunPage::STATUS_PENDING)
				->orderBy('page_number')->limit($limit)->get();

			foreach ($pending as $page) {
				$page->update(['status' => ContextSearchIndexRunPage::STATUS_EXTRACTING]);
			}

			$remaining = ContextSearchIndexRunPage::query()->where('run_resource_id', $item->getKey())
				->whereIn('status', [ContextSearchIndexRunPage::STATUS_PENDING, ContextSearchIndexRunPage::STATUS_EXTRACTING, ContextSearchIndexRunPage::STATUS_EXTRACTED, ContextSearchIndexRunPage::STATUS_INDEXING])->exists();

			return [$pending->pluck('id')->all(), $remaining ? NULL : (int)$item->getKey()];
		});

		foreach ($result[0] as $pageId) {
			ExtractContextSearchPage::dispatch((int)$pageId);
		}

		if ($result[1] !== NULL) {
			$this->finishResource($result[1]);
		}
	}

	private function finishResource(int $runResourceId): void {
		$result = DB::transaction(function () use ($runResourceId): ?array {
			$item = ContextSearchIndexRunResource::query()->lockForUpdate()->find($runResourceId);
			if ($item === NULL || $item->status !== ContextSearchIndexRunResource::STATUS_RUNNING) {
				return NULL;
			}

			$pages = ContextSearchIndexRunPage::query()->where('run_resource_id', $item->getKey())->get();
			if ($pages->count() !== $item->page_count || $pages->contains(fn($page) => !in_array($page->status, [ContextSearchIndexRunPage::STATUS_INDEXED, ContextSearchIndexRunPage::STATUS_SKIPPED, ContextSearchIndexRunPage::STATUS_FAILED, ContextSearchIndexRunPage::STATUS_STALE], TRUE))) {
				return NULL;
			}

			$run      = ContextSearchIndexRun::query()->findOrFail($item->run_id);
			$resource = (new Resource())->newQueryWithoutScopes()->find($item->resource_id);
			try {
				$sourceCurrent = $resource !== NULL && hash_equals((string)$item->source_revision, app(ContextSearchSourceSnapshot::class)->revision($resource));
			} catch (RuntimeException) {
				$sourceCurrent = FALSE;
			}
			$failed      = !$sourceCurrent || $pages->contains(fn($page) => in_array($page->status, [ContextSearchIndexRunPage::STATUS_FAILED, ContextSearchIndexRunPage::STATUS_STALE], TRUE));
			$chunks      = (int)$pages->sum('indexed_chunks');
			$skipped     = $pages->where('status', ContextSearchIndexRunPage::STATUS_SKIPPED)->count();
			$skipReasons = [];
			foreach ($pages->where('status', ContextSearchIndexRunPage::STATUS_SKIPPED) as $page) {
				foreach (($page->skip_reasons ?: [$page->failure_code ?: 'empty_page']) as $reason) {
					$skipReasons[$reason] = ($skipReasons[$reason] ?? 0) + 1;
				}
			}
			$item->update([
				'status'          => $failed ? ContextSearchIndexRunResource::STATUS_FAILED : ($chunks === 0 ? ContextSearchIndexRunResource::STATUS_SKIPPED_LOW_QUALITY : ContextSearchIndexRunResource::STATUS_COMPLETED),
				'indexed_chunks'  => $chunks,
				'indexed_pages'   => $pages->where('status', ContextSearchIndexRunPage::STATUS_INDEXED)->count(),
				'skipped_pages'   => $skipped,
				'skip_reasons'    => $skipReasons,
				'failure_message' => $failed ? 'One or more source pages could not be indexed.' : NULL,
			]);

			$oldRevision = NULL;
			if (!$failed) {
				$previous    = ContextSearchResourcePublication::query()
					->where('collection_name', $run->collection_name)
					->where('embedding_profile', $run->embedding_profile)
					->where('resource_id', $item->resource_id)
					->lockForUpdate()->first();
				$oldRevision = $previous?->index_revision;
				ContextSearchResourcePublication::query()->updateOrCreate(
					['collection_name' => $run->collection_name, 'embedding_profile' => $run->embedding_profile, 'resource_id' => $item->resource_id],
					[
						'document_revision' => $chunks === 0 ? NULL : $item->source_revision,
						'index_revision'    => $chunks === 0 ? NULL : $item->index_revision,
						'status'            => $chunks === 0 ? ContextSearchResourcePublication::STATUS_WITHDRAWN : ContextSearchResourcePublication::STATUS_PUBLISHED,
					],
				);
			}

			$newRevision = $chunks === 0 ? NULL : $item->index_revision;

			return [$item->run_id, !$failed && $oldRevision !== NULL && $oldRevision !== $newRevision
				? [$run->collection_name, $run->embedding_profile, $item->resource_id, $oldRevision, $newRevision]
				: NULL];
		});

		if ($result !== NULL) {
			if ($result[1] !== NULL) {
				CleanupContextSearchRevision::dispatch(...$result[1]);
			}
			$this->refreshRun($result[0]);
			$this->advanceRun($result[0]);
		}
	}

	private function refreshRun(string $runId): void {
		DB::transaction(function () use ($runId): void {
			$run = ContextSearchIndexRun::query()->lockForUpdate()->find($runId);
			if ($run === NULL) {
				return;
			}

			$completed                = ContextSearchIndexRunResource::query()->where('run_id', $runId)
				->whereIn('status', [ContextSearchIndexRunResource::STATUS_COMPLETED, ContextSearchIndexRunResource::STATUS_SKIPPED_LOW_QUALITY])->count();
			$failed                   = ContextSearchIndexRunResource::query()->where('run_id', $runId)
				->where('status', ContextSearchIndexRunResource::STATUS_FAILED)->count();
			$run->processed_resources = $completed;
			$run->failed_resources    = $failed;
			if ($completed + $failed === $run->total_resources) {
				$run->status      = $failed === 0 ? ContextSearchIndexRun::STATUS_COMPLETED : ContextSearchIndexRun::STATUS_FAILED;
				$run->finished_at = now();
			}
			$run->save();
		});
	}

	public function advanceRun(string $runId): void {
		if (!$this->capacity->allowsNewBackgroundJobs()) {
			return;
		}

		$resourceIds = DB::transaction(function () use ($runId): array {
			$run = ContextSearchIndexRun::query()->lockForUpdate()->find($runId);
			if ($run === NULL || $run->status !== ContextSearchIndexRun::STATUS_RUNNING) {
				return [];
			}

			$active  = ContextSearchIndexRunResource::query()->where('run_id', $runId)
				->where('status', ContextSearchIndexRunResource::STATUS_RUNNING)->count();
			$limit   = max(0, (int)config('context_search.indexing.resource_window', 2) - $active);
			$pending = ContextSearchIndexRunResource::query()->where('run_id', $runId)
				->where('status', ContextSearchIndexRun::STATUS_PENDING)
				->orderBy('resource_id')->limit($limit)->get();

			foreach ($pending as $item) {
				$item->update(['status' => ContextSearchIndexRunResource::STATUS_RUNNING]);
			}

			return $pending->pluck('resource_id')->all();
		});

		foreach ($resourceIds as $resourceId) {
			IndexContextSearchResource::dispatch($runId, (int)$resourceId);
		}
	}

	public function currentResource(ContextSearchIndexRunPage $page, ContextSearchSourceSnapshot $snapshot, EmbeddingProfile $profile): ?Resource {
		$item = ContextSearchIndexRunResource::query()->find($page->run_resource_id);
		if ($item === NULL || $item->status !== ContextSearchIndexRunResource::STATUS_RUNNING) {
			return NULL;
		}

		$run = ContextSearchIndexRun::query()->find($item->run_id);
		if ($run === NULL || $run->status !== ContextSearchIndexRun::STATUS_RUNNING
			|| $run->embedding_profile !== $profile->id()
			|| $item->extraction_profile !== $snapshot->extractionProfile()
			|| $item->chunking_profile !== $snapshot->chunkingProfile()
			|| $item->index_revision !== $snapshot->indexRevision((string)$item->source_revision, $profile->id())) {
			return NULL;
		}

		$resource = (new Resource())->newQueryWithoutScopes()->find($item->resource_id);
		if ($resource === NULL) {
			return NULL;
		}

		try {
			return hash_equals((string)$item->source_revision, $snapshot->revision($resource)) ? $resource : NULL;
		} catch (RuntimeException) {
			return NULL;
		}
	}

	public function finishPage(int $pageId, string $status, int $chunks = 0, ?string $failureCode = NULL): void {
		$runResourceId = DB::transaction(function () use ($pageId, $status, $chunks, $failureCode): ?int {
			$page = ContextSearchIndexRunPage::query()->lockForUpdate()->find($pageId);
			if ($page === NULL || in_array($page->status, [ContextSearchIndexRunPage::STATUS_INDEXED, ContextSearchIndexRunPage::STATUS_SKIPPED, ContextSearchIndexRunPage::STATUS_FAILED, ContextSearchIndexRunPage::STATUS_STALE], TRUE)) {
				return NULL;
			}

			$page->update(['status' => $status, 'indexed_chunks' => $chunks, 'failure_code' => $failureCode]);

			return (int)$page->run_resource_id;
		});

		if ($runResourceId !== NULL) {
			$this->advanceResource($runResourceId);
		}
	}

	public function failResource(int $runResourceId, string $code): void {
		$runId = DB::transaction(function () use ($runResourceId, $code): ?string {
			$item = ContextSearchIndexRunResource::query()->lockForUpdate()->find($runResourceId);
			if ($item === NULL || in_array($item->status, [ContextSearchIndexRunResource::STATUS_COMPLETED, ContextSearchIndexRunResource::STATUS_SKIPPED_LOW_QUALITY, ContextSearchIndexRunResource::STATUS_FAILED], TRUE)) {
				return NULL;
			}

			$item->update(['status' => ContextSearchIndexRunResource::STATUS_FAILED, 'failure_message' => $code]);

			return $item->run_id;
		});

		if ($runId !== NULL) {
			$this->refreshRun($runId);
			$this->advanceRun($runId);
		}
	}
}
