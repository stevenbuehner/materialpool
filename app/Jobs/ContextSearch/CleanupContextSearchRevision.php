<?php

namespace App\Jobs\ContextSearch;

use App\Models\ContextSearchResourcePublication;
use App\Services\ContextSearch\Qdrant\QdrantClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

final class CleanupContextSearchRevision implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 60;
	public $tries   = 3;
	public $backoff = [30, 120];

	public function __construct(
		private readonly string  $collection,
		private readonly string  $profile,
		private readonly int     $resourceId,
		private readonly string  $oldRevision,
		private readonly ?string $newRevision,
	) {
		$this->onConnection((string)config('context_search.indexing.connection'));
		$this->onQueue((string)config('context_search.indexing.upsert_queue'));
	}

	public function handle(QdrantClient $qdrant): void {
		DB::transaction(function () use ($qdrant): void {
			$publication = ContextSearchResourcePublication::query()->where('collection_name', $this->collection)
				->where('embedding_profile', $this->profile)
				->where('resource_id', $this->resourceId)
				->lockForUpdate()->first();

			if ($publication !== NULL
				&& $publication->index_revision === $this->newRevision
				&& $publication->status === ($this->newRevision === NULL ? ContextSearchResourcePublication::STATUS_WITHDRAWN : ContextSearchResourcePublication::STATUS_PUBLISHED)
				&& $this->oldRevision !== $this->newRevision) {
				// Hold the publication lock until Qdrant confirms deletion: a rollback cannot
				// republish the old revision in the gap between check and remote deletion.
				$qdrant->deleteResourceRevisionPoints($this->collection, $this->resourceId, $this->profile, $this->oldRevision);
			}
		});
	}
}
