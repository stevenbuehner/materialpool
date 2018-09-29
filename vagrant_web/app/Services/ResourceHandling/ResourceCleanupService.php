<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\ResourceHandling;


use App\Jobs\CheckLonelyMaterial;
use App\Models\File;
use App\Models\Resource;
use Illuminate\Support\Facades\Log;

class ResourceCleanupService {

	public function cleanUp(Resource $resource) {

		$this->cleanUpCache($resource);

		if ($resource instanceof File) {
			$this->cleanUpFileResource($resource);
		}

		$this->cleanUpDB($resource);

	}

	public function cleanUpCache(Resource $resource) {
		// Todo: Implement me
	}

	public function cleanUpFileResource(File $resource) {

		if ($resource->hasLocalFile()) {
			try {
				$resource->deleteLocalFile();

				Log::info('Cleaned up local file of Resource (ID: ' . $resource->id . ')');

			} catch (\Exception $e) {
				Log::error('Error while cleanup / deleting local file', [
					'message' => $e->getMessage(),
					'trace'   => $e->getTrace()
				]);
			}
		}

	}

	public function cleanUpDB(Resource $resource) {

		// Detach Materials
		$materials = $resource->materials;

		$resource->materials()->detach();

		// Check lonely materials
		foreach ($materials as $material) {
			CheckLonelyMaterial::dispatch($material);
		}


		// Delete Foreign IDs
		$resource->foreignIds()->delete();

		// Delete Resource itself
		$resource->delete();

	}

}