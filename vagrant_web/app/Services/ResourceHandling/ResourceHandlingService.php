<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\ResourceHandling;


use App\Events\ResourceWasDetached;
use App\Models\Resource;

class ResourceHandlingService {

	public function detachAllMaterials(Resource $resource) {

		foreach ($resource->materials as $material) {
			$resource->materials()->detach($material->id);
			event(new ResourceWasDetached($material, $resource));
		}

	}

	public function detachAllForeignIds(Resource $resource) {

		$resource->foreignIds()->delete();

	}

}