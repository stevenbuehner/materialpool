<?php
/**
 * This file was created by  steven
 * Created: 21.10.17 20:16
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\ResourceHandling;


use App\Events\ResourceWasAttached;
use App\Events\ResourceWasDetached;
use App\Listeners\CheckDuplicateMaterials;
use App\Models\Resource as Res;
use App\Services\ResourceHandling\Exceptions\MissingResourceHashException;
use Illuminate\Database\QueryException;
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

		$masterResource = Res::where('content_hash', '=', $hash)->take(1)->get()->first();

		$this->mergeDuplicatesOfResource($masterResource);

	}

	public function mergeDuplicatesOfResource(Res $resourceToCheck) {

		if (empty($resourceToCheck->content_hash)) {
			throw new MissingResourceHashException();
		}

		Res::where('content_hash', '=', $resourceToCheck->content_hash)
			->where('id', '!=', $resourceToCheck->id)
			->orderBy('id')
			->chunk(30, function ($slaveResources) use ($resourceToCheck) {
				foreach ($slaveResources as $slave) {
					$this->migrateSlaveIntoMasterResource($slave, $resourceToCheck);
					// CheckDuplicateMaterials::dispatch($resourceToCheck);
				}
			});

	}

	public function migrateSlaveIntoMasterResource(Res $slaveResource, Res $masterResource) {

		// Relationen nachladen, um anschließend zu wissen, wo Events gefeuert werden müssen
		$slaveResource->loadMissing('materials');
		$masterResource->loadMissing('materials');

		DB::beginTransaction();

		// Update material_resource
		try {
			DB::table('material_resource')
				->where('resource_id', '=', $slaveResource->id)
				->update(['resource_id' => $masterResource->id]);
		} catch (QueryException $e) {
			if ($e->getPrevious() instanceof \PDOException &&
				strpos($e->getPrevious()->getMessage(), 'Duplicate entry') !== FALSE) {
				// the Entry exists already --> ignore the exception
				DB::table('material_resource')
					->where('resource_id', '=', $slaveResource->id)
					->delete();

			} else {
				DB::rollBack();
				throw($e);
			}
		}

		// Werfe die detach Funktionen für alle losgelösten Resourcen
		foreach ($slaveResource->materials as $m) {
			event(new ResourceWasDetached($m, $slaveResource));
		}

		// Werfe die attach Funktionen für alle Materialien die eine Resource NEU/Zusätzlichcxl bekommen haben
		// Ignoriere Materialien, die bereits davor mit der Resource verknüpft waren. Denn dort hat sich auch nichts geändert. Auch die Limitations nicht.
		$newAttachedMaterials = $slaveResource->materials->except($masterResource->materials->modelKeys());
		foreach ($newAttachedMaterials as $m) {
			event(new ResourceWasAttached($m, $masterResource));
		}

		// Update resource_foreign_ids
		try {
			DB::table('resource_foreign_ids')
				->where('resource_id', '=', $slaveResource->id)
				->update(['resource_id' => $masterResource->id]);
		} catch (\Exception $e) {
			DB::rollBack();
			throw($e);
		}

		DB::commit();

		Log::info('Deleting duplicate resource entry in db ' . $slaveResource->id . ' in favor of ' . $masterResource->id);
		$this->fileHandlingService->deleteResourceCompletely($slaveResource);

	}

}