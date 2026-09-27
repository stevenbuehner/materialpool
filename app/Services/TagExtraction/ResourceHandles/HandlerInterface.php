<?php

namespace App\Services\TagExtraction\ResourceHandles;

use App\Models\Resource;
use App\Services\TagExtraction\Properties\Property;

interface HandlerInterface {

	/**
	 * Returns an array of possible metaData
	 *
	 * @param Resource $resource
	 * @return Property[]
	 */
	public function handle(Resource $resource);
}