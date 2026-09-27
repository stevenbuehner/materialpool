<?php
/**
 * This file was created by  steven
 * Created: 21.10.17 20:16
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\ResourceHandling;


use App\Events\ResourceWasAttached;
use App\Events\ResourceWasDetached;
use App\Models\Resource as Res;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ResourceDuplicationHandlingService {

	protected $fileHandlingService;

	public function __construct(FileHandlingService $fileHandlingService) {
		$this->fileHandlingService = $fileHandlingService;
	}

	public function mergeAllDuplicates() {
		DB::table('resources')
			->select(['content_hash', DB::raw('COUNT(id) as count')])
			->whereNotNull('content_hash')
			->groupBy(['content_hash'])
			->orderBy('count', 'desc')
			->having(DB::raw('COUNT(id)'), '>=', 2)
			->chunk(100, function ($resources) {

				foreach ($resources as $resTable) {
					$hash  = $resTable->content_hash;
					$count = (int)$resTable->count;

					$this->migrateResourcesWithHash($hash);
				}
			});
	}

	protected function migrateResourcesWithHash($hash) {
		$masterResource = Res::query()
			->where('content_hash', $hash)
			->orderBy('id')
			->first();

		if ($masterResource !== NULL) {
			$this->mergeDuplicatesOfResource($masterResource);
		}
	}

	public function mergeDuplicatesOfResource(Res $resourceToCheck) {

		if (empty($resourceToCheck->content_hash)) {
			return;
		}

		$masterResource = Res::query()
			->where('content_hash', $resourceToCheck->content_hash)
			->orderBy('id')
			->first();

		if ($masterResource === NULL) {
			return;
		}

		Res::query()
			->where('content_hash', $masterResource->content_hash)
			->where('id', '!=', $masterResource->id)
			->orderBy('id')
			->chunk(30, function ($slaveResources) use ($masterResource) {
				foreach ($slaveResources as $slave) {
					$this->migrateSlaveIntoMasterResource($slave, $masterResource);
					// CheckDuplicateMaterials::dispatch($resourceToCheck);
				}
			});

	}

	public function migrateSlaveIntoMasterResource(Res $slaveResource, Res $masterResource) {

		// Relationen nachladen, um anschließend zu wissen, wo Events gefeuert werden müssen
		$slaveResource->load('materials');
		$masterResource->load('materials');

		$masterMaterialIds    = $masterResource->materials->modelKeys();
		$newAttachedMaterials = $slaveResource->materials->except($masterMaterialIds);

		DB::transaction(function () use ($slaveResource, $masterResource, $masterMaterialIds): void {
			foreach ($slaveResource->materials as $material) {
				$pivotQuery = DB::table('material_resource')
					->where('resource_id', $slaveResource->id)
					->where('material_id', $material->id);

				if (in_array($material->id, $masterMaterialIds, TRUE)) {
					$pivotQuery->delete();

					continue;
				}

				$pivotQuery->update(['resource_id' => $masterResource->id]);
			}

			DB::table('resource_foreign_ids')
				->where('resource_id', '=', $slaveResource->id)
				->update(['resource_id' => $masterResource->id]);
		});

		foreach ($slaveResource->materials as $material) {
			event(new ResourceWasDetached($material, $slaveResource));
		}

		foreach ($newAttachedMaterials as $material) {
			event(new ResourceWasAttached($material, $masterResource));
		}

		Log::info('Deleting duplicate resource entry in db ' . $slaveResource->id . ' in favor of ' . $masterResource->id);
		$this->fileHandlingService->deleteResourceCompletely($slaveResource);

	}

}
