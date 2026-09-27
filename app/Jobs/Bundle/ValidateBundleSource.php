<?php

namespace App\Jobs\Bundle;

use App\Enums\BundleImportOperation;
use App\Exceptions\Bundles\BundleSourceValidationException;
use App\Models\BundleImportRun;
use App\Services\Bundles\BundleImportOrchestrator;
use App\Services\Bundles\BundleSourceValidator;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ValidateBundleSource implements ShouldQueue {
	use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 120;
	public $tries   = 2;
	public $backoff = 5;

	public function __construct(private string $runId) {
	}

	public function handle(BundleSourceValidator $validator): void {
		if ($this->batch()?->cancelled()) {
			return;
		}

		$run = BundleImportRun::query()->with('bundle')->findOrFail($this->runId);
		if ($run->operation === BundleImportOperation::Uninstall) {
			return;
		}

		try {
			$source = $validator->validate($run->bundle);
		} catch (BundleSourceValidationException $exception) {
			app(BundleImportOrchestrator::class)->fail($run->id, $exception->failureCode);

			throw $exception;
		}

		$run->update(['source_fingerprint' => $source['source_fingerprint'], 'source_warnings' => $source['warnings']]);
	}

	public function middleware(): array {
		return [(new WithoutOverlapping('bundle-run:' . $this->runId . ':validate'))
			        ->shared()
			        ->releaseAfter(5)
			        ->expireAfter(180)];
	}
}
