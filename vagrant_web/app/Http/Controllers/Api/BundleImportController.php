<?php

namespace App\Http\Controllers\Api;

use App\Enums\BundleImportOperation;
use App\Exceptions\Bundles\BundleImportConflictException;
use App\Exceptions\Bundles\BundleSourceValidationException;
use App\Http\Controllers\Controller as BaseController;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use App\Services\Bundles\BundleImportOrchestrator;
use App\Services\Bundles\BundleImportRunService;
use App\Services\Bundles\BundleQueueService;
use App\Services\Bundles\BundlesService;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Bus;

class BundleImportController extends BaseController {
	public function __construct(
		private BundlesService $bundlesService,
		private BundleQueueService $bundleQueueService,
		private BundleImportRunService $runService,
		private BundleImportOrchestrator $orchestrator,
	) {
		$this->middleware(['auth:api']);
	}

	public function index(): array {
		$bundles = $this->bundlesService->updateInstalledBundleInfos();
		$localInfos = collect();

		foreach ($bundles as $bundle) {
			try {
				$info = $this->bundlesService->getLocalBundleData($bundle);
				if ($info['version'] !== $bundle->installed_version && $bundle->update_available !== TRUE) {
					$bundle->update(['update_available' => TRUE]);
				}
				$localInfos->push($info);
			} catch (FileNotFoundException) {
				// Fehlende Quellen bleiben für eine spätere Deinstallation sichtbar.
			}
		}

		return ['bundles' => $bundles, 'infos' => $localInfos];
	}

	public function getBundleIcon(Bundle $bundle) {
		if ($bundle->icon !== NULL) {
			$path = $bundle->container_root . '/' . $bundle->icon;
			$disk = $this->bundlesService->getBundleDisk();
			if ($disk->exists($path)) {
				return $disk->response($path);
			}
		}

		abort(404);
	}

	public function show(Bundle $bundle): Bundle {
		return $bundle;
	}

	public function initUpdate(Bundle $bundle): JsonResponse {
		try {
			$source = $this->bundlesService->getLocalBundleData($bundle);
		} catch (FileNotFoundException) {
			return $this->error('bundle_source_missing', 422);
		}

		if ($source['version'] === $bundle->installed_version) {
			return response()->json(['updateAvailable' => FALSE, 'continueUpdate' => FALSE, 'openJobs' => 0, 'run' => NULL]);
		}

		return $this->startRun($bundle, BundleImportOperation::Update, $source['version']);
	}

	public function initUninstall(Bundle $bundle): JsonResponse {
		return $this->startRun($bundle, BundleImportOperation::Uninstall, $bundle->installed_version);
	}

	public function status(Bundle $bundle, BundleImportRun $run): array {
		abort_unless($run->bundle_id === $bundle->id, 404);

		return $this->serializeRun($run);
	}

	public function activeStatus(Bundle $bundle): array {
		$run = BundleImportRun::query()->where('bundle_id', $bundle->id)->whereNotNull('active_slot')->latest('created_at')->firstOrFail();

		return $this->serializeRun($run);
	}

	public function runJobs(Bundle $bundle): array {
		$run = BundleImportRun::query()->where('bundle_id', $bundle->id)->whereNotNull('active_slot')->latest('created_at')->first();
		if ($run === NULL) {
			return ['done' => 0, 'open' => 0, 'bundle' => $bundle->fresh()];
		}

		$start = microtime(TRUE);
		$options = new WorkerOptions(name: 'bundle-browser', backoff: 5, memory: 128, timeout: 120, sleep: 0, maxTries: 0);
		$worker = resolve('queue.worker');
		$finishedJobs = 0;
		$openJobs = $this->bundleQueueService->countJobsInBundleQueue($bundle);

		while (microtime(TRUE) - $start <= 5 && $openJobs > 0) {
			/** @var Worker $worker */
			$worker->runNextJob('database', $run->queue_name, $options);
			$finishedJobs++;
			$openJobs = $this->bundleQueueService->countJobsInBundleQueue($bundle);
		}

		$freshRun = $run->fresh();
		$result = ['done' => $finishedJobs, 'open' => $openJobs, 'run' => $this->serializeRun($freshRun)];
		if ($openJobs === 0 && $freshRun->active_slot === NULL) {
			$result['bundle'] = $bundle->fresh();
		}

		return $result;
	}

	private function startRun(Bundle $bundle, BundleImportOperation $operation, ?string $targetVersion): JsonResponse {
		try {
			$run = $this->runService->start($bundle, $operation, $targetVersion, request()->user());
			$this->orchestrator->start($run);
		} catch (BundleImportConflictException $exception) {
			return response()->json(['error' => ['code' => 'bundle_import_conflict', 'message' => 'Für dieses Bundle läuft bereits eine andere Operation.', 'run_id' => $exception->activeRun->id]], 409);
		} catch (BundleSourceValidationException $exception) {
			return $this->error($exception->failureCode, 422);
		}

		return response()->json([
			'updateAvailable' => TRUE,
			'continueUpdate' => $run->wasRecentlyCreated === FALSE,
			'openJobs' => $this->bundleQueueService->countJobsInBundleQueue($bundle),
			'run' => $this->serializeRun($run->fresh()),
		], 202);
	}

	private function serializeRun(BundleImportRun $run): array {
		$batch = $run->current_batch_id === NULL ? NULL : Bus::findBatch($run->current_batch_id);
		$total = $run->expected_jobs;
		$processed = $run->processed_jobs;
		$failed = 0;
		if ($batch instanceof Batch) {
			$total = max($total, $batch->totalJobs);
			$processed = max($processed, $batch->processedJobs());
			$failed = $batch->failedJobs;
		}

		return [
			'id' => $run->id,
			'bundle_id' => $run->bundle_id,
			'operation' => $run->operation->value,
			'status' => $run->status->value,
			'phase' => $run->phase->value,
			'target_version' => $run->target_version,
			'progress' => ['total' => $total, 'processed' => $processed, 'failed' => $failed, 'percentage' => $total === 0 ? 0 : (int)floor($processed / $total * 100)],
			'failure' => $run->failure_code === NULL ? NULL : ['code' => $run->failure_code, 'message' => $run->failure_message],
			'warnings' => $this->serializeWarnings($run->source_warnings),
		];
	}

	private function serializeWarnings(?array $warnings): array {
		return $warnings['summary'] ?? ['skipped_materials' => 0, 'skipped_resources' => 0, 'reasons' => []];
	}

	private function error(string $code, int $status): JsonResponse {
		return response()->json(['error' => ['code' => $code, 'message' => 'Das Bundle kann nicht verarbeitet werden.', 'run_id' => NULL]], $status);
	}
}
