<?php

namespace App\Services\Processors;

use App\Jobs\CheckDuplicateResources;
use App\Models\File;
use App\Models\Resource;

class ResourceHashProcessor {

	public function updateResourceHash(Resource $resource) {

		if ($resource instanceof File) {
			$sha1 = sha1($resource->getLocalFile());
		} else {
			$sha1 = sha1(json_encode($resource->options));
		}

		if ($resource->content_hash != $sha1) {
			$resource->content_hash = $sha1;
			$resource->save();

			CheckDuplicateResources::dispatch($resource);

		}
	}

}