<?php

namespace App\Services\Processors;

use App\Models\File;
use App\Models\Resource;

class ResourceHashProcessor {

	public function createResourceHash(Resource $resource) {

		if ($resource instanceof File) {
			$sha1 = sha1($resource->getLocalFile());

			$resource->file_hash = $sha1;
			$resource->save();
		}

	}

}