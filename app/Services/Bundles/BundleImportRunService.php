<?php

namespace App\Services\Bundles;

use App\Enums\BundleImportOperation;
use App\Exceptions\Bundles\BundleImportConflictException;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BundleImportRunService {
	public function __construct(private BundleQueueService $bundleQueueService) {
	}

	public function start(Bundle $bundle, BundleImportOperation $operation, ?string $targetVersion, ?User $requestedBy = NULL): BundleImportRun {
		// Die Cache-Sperre schützt den Einstieg; die Datenbanksperre sichert den Zustand auch bei parallelen Workern.
		return Cache::lock('bundle-import:start:' . $bundle->id, 30)->block(5, function () use ($bundle, $operation, $targetVersion, $requestedBy): BundleImportRun {
			return DB::transaction(function () use ($bundle, $operation, $targetVersion, $requestedBy): BundleImportRun {
				$lockedBundle = Bundle::query()->lockForUpdate()->findOrFail($bundle->id);
				$activeRun = BundleImportRun::query()
					->where('bundle_id', $lockedBundle->id)
					->whereNotNull('active_slot')
					->lockForUpdate()
					->first();

				if ($activeRun !== NULL) {
					if ($activeRun->operation === $operation && $activeRun->target_version === $targetVersion) {
						return $activeRun;
					}

					throw new BundleImportConflictException($activeRun);
				}

				return BundleImportRun::query()->create([
					'bundle_id' => $lockedBundle->id,
					'requested_by' => $requestedBy?->id,
					'operation' => $operation,
					'target_version' => $targetVersion,
					'queue_name' => $this->bundleQueueService->getQueueName($lockedBundle),
				]);
			});
		});
	}
}
