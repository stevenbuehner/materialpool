<?php
namespace App\Services\TagExtraction\ResourceHandles;

use App\Models\Resource;

interface HandleInterface {

	/**
	 * Returns an array of possible metaData
	 * @param Resource $resource
	 * @return array metaData
	 */
	public function handle(Resource $resource);
}