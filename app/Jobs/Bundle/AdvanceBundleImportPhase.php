<?php

namespace App\Jobs\Bundle;

use App\Services\Bundles\BundleImportOrchestrator;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Complements Laravel's batch callbacks when a batch completes before its ID
 * could be persisted on the import run.
 */
class AdvanceBundleImportPhase implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 120;
	public $tries   = 0;

	public function __construct(private string $runId, private string $batchId) {
	}

	public function handle(BundleImportOrchestrator $orchestrator): void {
		if (!$orchestrator->advanceCompletedBatch($this->runId, $this->batchId)) {
			$this->release(5);
		}
	}

	public function retryUntil(): DateTimeInterface {
		return now()->addMinutes(30);
	}
}
