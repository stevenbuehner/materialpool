<?php

namespace App\Services\Bundles;

use App\Enums\BundleImportOperation;
use App\Enums\BundleImportPhase;
use App\Enums\BundleImportStatus;
use App\Jobs\Bundle\DeleteMaterialIfNeeded;
use App\Jobs\Bundle\DeleteResourceIfNeeded;
use App\Jobs\Bundle\AdvanceBundleImportPhase;
use App\Jobs\Bundle\InsertOrUpdateMaterial;
use App\Jobs\Bundle\InsertOrUpdateResource;
use App\Jobs\Bundle\ValidateBundleSource;
use App\Models\BundleImportRun;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

class BundleImportOrchestrator {
	private const BATCH_COLUMNS = [
		BundleImportPhase::Validating->value => 'validation_batch_id',
		BundleImportPhase::DeletingMaterials->value => 'delete_materials_batch_id',
		BundleImportPhase::DeletingResources->value => 'delete_resources_batch_id',
		BundleImportPhase::UpsertingResources->value => 'resources_batch_id',
		BundleImportPhase::UpsertingMaterials->value => 'materials_batch_id',
	];

	public function __construct(private BundlesService $bundlesService) {
	}

	public function start(BundleImportRun $run): void {
		$runId = $run->id;
		$shouldDispatch = DB::transaction(function () use ($runId): bool {
			$lockedRun = BundleImportRun::query()->lockForUpdate()->findOrFail($runId);
			if ($lockedRun->status !== BundleImportStatus::Pending) {
				return FALSE;
			}

			$lockedRun->update([
				'status' => BundleImportStatus::Running,
				'phase' => BundleImportPhase::Validating,
				'started_at' => now(),
			]);

			return TRUE;
		});

		if ($shouldDispatch) {
			$this->dispatchCurrentPhase($runId);
		}
	}

	public function phaseSucceeded(string $runId, ?string $batchId = NULL): void {
		$nextPhase = DB::transaction(function () use ($runId, $batchId): ?BundleImportPhase {
			$run = BundleImportRun::query()->lockForUpdate()->findOrFail($runId);
			if ($run->status !== BundleImportStatus::Running || ($batchId !== NULL && $run->current_batch_id !== $batchId)) {
				return NULL;
			}

			$nextPhase = match ($run->phase) {
				BundleImportPhase::Validating => BundleImportPhase::DeletingMaterials,
				BundleImportPhase::DeletingMaterials => BundleImportPhase::DeletingResources,
				BundleImportPhase::DeletingResources => BundleImportPhase::UpsertingResources,
				BundleImportPhase::UpsertingResources => BundleImportPhase::UpsertingMaterials,
				BundleImportPhase::UpsertingMaterials => BundleImportPhase::Finalizing,
				default => NULL,
			};

			if ($nextPhase === NULL) {
				return NULL;
			}

			$run->update(['phase' => $nextPhase, 'current_batch_id' => NULL]);

			return $nextPhase;
		});

		if ($nextPhase === BundleImportPhase::Finalizing) {
			$this->finalize($runId);
		} elseif ($nextPhase !== NULL) {
			$this->dispatchCurrentPhase($runId);
		}
	}

	public function fail(string $runId, string $failureCode = 'bundle_import_failed'): void {
		DB::transaction(function () use ($runId, $failureCode): void {
			$run = BundleImportRun::query()->lockForUpdate()->findOrFail($runId);
			if (in_array($run->status, [BundleImportStatus::Succeeded, BundleImportStatus::Failed], TRUE)) {
				return;
			}

			$run->update([
				'status' => BundleImportStatus::Failed,
				'active_slot' => NULL,
				'failure_code' => $failureCode,
				'failure_message' => 'Der Bundle-Import konnte nicht abgeschlossen werden.',
				'result_summary' => $this->resultSummary($run, $failureCode),
				'finished_at' => now(),
			]);
		});
	}

	public function reconcile(string $runId, string $batchId): void {
		$batch = Bus::findBatch($batchId);
		if ($batch !== NULL && ($batch->cancelled() || $batch->failedJobs > 0)) {
			$this->fail($runId);
		}
	}

	/**
	 * Advance a completed batch after its ID has been persisted on the run.
	 *
	 * Laravel invokes batch callbacks from queue workers. A very fast worker can
	 * therefore finish between batch dispatch and storeBatch(). This coordinator
	 * is deliberately idempotent and closes that window.
	 */
	public function advanceCompletedBatch(string $runId, string $batchId): bool {
		$batch = Bus::findBatch($batchId);
		if ($batch === NULL || !$batch->finished()) {
			return FALSE;
		}

		if ($batch->cancelled() || $batch->failedJobs > 0) {
			$this->fail($runId);

			return TRUE;
		}

		$this->phaseSucceeded($runId, $batchId);

		return TRUE;
	}

	private function dispatchCurrentPhase(string $runId): void {
		$run = BundleImportRun::query()->with('bundle')->findOrFail($runId);
		if ($run->status !== BundleImportStatus::Running) {
			return;
		}

		$jobs = $this->jobsFor($run);
		if ($jobs === []) {
			$this->phaseSucceeded($runId);

			return;
		}

		$batch = Bus::batch($jobs)
			->name('bundle:' . $run->bundle_id . ':run:' . $run->id . ':' . $run->phase->value)
			->onConnection('database')
			->onQueue($run->queue_name)
			->then(function (Batch $batch) use ($runId): void {
				app(self::class)->phaseSucceeded($runId, $batch->id);
			})
			->catch(function (Batch $batch, Throwable $exception) use ($runId): void {
				app(self::class)->fail($runId);
			})
			->finally(function (Batch $batch) use ($runId): void {
				app(self::class)->reconcile($runId, $batch->id);
			})
			->dispatch();

		$this->storeBatch($runId, $run->phase, $batch->id);
		AdvanceBundleImportPhase::dispatch($runId, $batch->id)
			->onConnection('database')
			->onQueue($run->queue_name)
			->delay(now()->addSeconds(5));
	}

	private function jobsFor(BundleImportRun $run): array {
		$bundle = $run->bundle;
		$version = $run->target_version;
		$uninstall = $run->operation === BundleImportOperation::Uninstall;

		return match ($run->phase) {
			BundleImportPhase::Validating => [new ValidateBundleSource($run->id)],
			BundleImportPhase::DeletingMaterials => ForeignMaterialId::query()->where('bundle_id', $bundle->id)->get()->map(fn(ForeignMaterialId $foreignId) => new DeleteMaterialIfNeeded($bundle, $foreignId, $version, $uninstall))->all(),
			BundleImportPhase::DeletingResources => ForeignResourceId::query()->where('bundle_id', $bundle->id)->get()->map(fn(ForeignResourceId $foreignId) => new DeleteResourceIfNeeded($bundle, $foreignId, $version, $uninstall))->all(),
			BundleImportPhase::UpsertingResources => $uninstall ? [] : $this->resourceJobs($run),
			BundleImportPhase::UpsertingMaterials => $uninstall ? [] : $this->materialJobs($run),
			default => [],
		};
	}

	private function resourceJobs(BundleImportRun $run): array {
		$source = $this->bundlesService->getLocalBundleData($run->bundle);
		$invalidFileUuids = $run->source_warnings['file_uuids'] ?? [];
		$jobs = [];
		for ($page = 1; ($files = collect($this->bundlesService->getBundleFiles($source, $page)))->isNotEmpty(); $page++) {
			foreach ($files as $file) {
				if (in_array($file->uuid, $invalidFileUuids, TRUE)) {
					continue;
				}
				$jobs[] = new InsertOrUpdateResource($run->bundle, $file, $run->target_version);
			}
		}

		return $jobs;
	}

	private function materialJobs(BundleImportRun $run): array {
		$source = $this->bundlesService->getLocalBundleData($run->bundle);
		$invalidMaterialIds = $run->source_warnings['material_ids'] ?? [];
		$jobs = [];
		for ($page = 1; ($materials = collect($this->bundlesService->getBundleMaterials($source, $page)))->isNotEmpty(); $page++) {
			foreach ($materials as $material) {
				if (in_array((int)$material->id, $invalidMaterialIds, TRUE)) {
					continue;
				}
				$jobs[] = new InsertOrUpdateMaterial($run->bundle, $material, $run->target_version);
			}
		}

		return $jobs;
	}

	private function storeBatch(string $runId, BundleImportPhase $phase, string $batchId): void {
		$batchColumn = self::BATCH_COLUMNS[$phase->value];
		BundleImportRun::query()->whereKey($runId)->update([$batchColumn => $batchId, 'current_batch_id' => $batchId]);
	}

	private function finalize(string $runId): void {
		DB::transaction(function () use ($runId): void {
			$run = BundleImportRun::query()->with('bundle')->lockForUpdate()->findOrFail($runId);
			if ($run->status !== BundleImportStatus::Running) {
				return;
			}

			$bundle = $run->bundle;
			if ($run->operation === BundleImportOperation::Uninstall) {
				$bundle->update(['installed_version' => NULL, 'update_available' => TRUE, 'is_installed' => FALSE]);
			} else {
				$bundle->update(['installed_version' => $run->target_version, 'update_available' => FALSE, 'is_installed' => TRUE]);
			}

			$run->update([
				'status' => BundleImportStatus::Succeeded,
				'active_slot' => NULL,
				'result_summary' => $this->resultSummary($run),
				'finished_at' => now(),
			]);
		});
	}

	private function resultSummary(BundleImportRun $run, ?string $failureCode = NULL): array {
		$warnings = $run->source_warnings ?? [];
		$materials = $this->batchSummary($run->materials_batch_id, count($warnings['material_ids'] ?? []));
		$resources = $this->batchSummary($run->resources_batch_id, count($warnings['file_uuids'] ?? []));
		$removedMaterials = $this->batchSummary($run->delete_materials_batch_id);
		$removedResources = $this->batchSummary($run->delete_resources_batch_id);
		$failedJobs = $materials['failed'] + $resources['failed'] + $removedMaterials['failed'] + $removedResources['failed'];

		return [
			'materials' => $materials,
			'resources' => $resources,
			'removed' => ['materials' => $removedMaterials, 'resources' => $removedResources],
			'errors' => ['count' => max($failedJobs, $failureCode === NULL ? 0 : 1), 'code' => $failureCode],
		];
	}

	private function batchSummary(?string $batchId, int $skipped = 0): array {
		$batch = $batchId === NULL ? NULL : Bus::findBatch($batchId);
		$processed = $batch?->processedJobs() ?? 0;
		$failed = $batch?->failedJobs ?? 0;

		return ['successful' => max($processed - $failed, 0), 'skipped' => $skipped, 'failed' => $failed];
	}
}
