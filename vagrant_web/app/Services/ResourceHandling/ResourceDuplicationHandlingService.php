<?php
/**
 * This file was created by  steven
 * Created: 21.10.17 20:16
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\ResourceHandling;


use App\Jobs\CheckDuplicateMaterials;
use App\Models\File;
use App\Models\Resource as Res;
use App\Services\ResourceHandling\Exceptions\MissingResourceHashException;
use Doctrine\DBAL\Driver\PDOException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ResourceDuplicationHandlingService {

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
				  $count = (int) $resTable->count;

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

	protected function migrateSlaveIntoMasterResource(Res $slaveResource, Res $masterResource) {

		// FixMe: Überarbeiten! Werfe die richtigen Events (ResourceModified, Deleted, Attached, Detached, ...)
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

		if ($slaveResource instanceof File && $slaveResource->local_path !== $masterResource->local_path) {
			Log::info('Deleting duplicate File of Resource: ' . $slaveResource->local_path);
			$slaveResource->deleteLocalFile();
		}

		Log::info('Deleting duplicate resource entry in db ' . $slaveResource->id . ' in favor of ' . $masterResource->id);
		$slaveResource->delete();
	}

}