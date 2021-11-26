<?php

namespace App\Services\Processors;

use App\Models\File;
use App\Models\Resource;
use App\Services\Processors\Exceptions\ResourceNotHashable;

class ResourceHashProcessor {

	/**
	 * @param Resource $resource
	 * @return bool true if hash has changed
	 * @throws ResourceNotHashable
	 */
	public function updateResourceHash(Resource $resource) {

		if (!$resource instanceof File && !$resource instanceof ContentHashProviderInterface) {
			throw new ResourceNotHashable($resource);
		}

		try {
			if ($resource instanceof File) {

				if ($resource->hasLocalFile()) {
					$stream = $resource->getLocalFileStream();
				} else if ($resource->hasRemoteFile()) {
					$stream = $resource->getRemoteFileStream();
				}

				if ($stream === FALSE) {
					throw new ResourceNotHashable($resource);
				}

				$algo = 'sha1';
				$hc   = hash_init($algo); // hash_algos()

				hash_update_stream($hc, $stream);

				$sha1 = hash_final($hc);

			} else if ($resource instanceof ContentHashProviderInterface) {
				$sha1 = sha1(json_encode($resource->getContentsForHash()));
			}
		} catch (\Exception $e) {
			throw new ResourceNotHashable($resource, 0, $e);
		}


		if ($resource->content_hash != $sha1) {
			$resource->content_hash = $sha1;
			$resource->save();

			return TRUE;

		}

		return FALSE;
	}

}