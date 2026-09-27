<?php

namespace App\Services\Processors;

use App\Models\File;
use App\Models\Resource;
use App\Models\Text;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Flysystem\UnableToRetrieveMetadata;

class ResourceFilesizeProcessor {
	public function updateResourceFilesize(Resource $resource): bool {
		$filesize = $this->calculate($resource);

		if ($resource->getAttribute('filesize') === $filesize) {
			if (!array_key_exists('filesize', $resource->getAttributes())) {
				$resource->setAttribute('filesize', $filesize);
				$resource->syncOriginalAttribute('filesize');
			}

			return FALSE;
		}

		DB::table($resource->getTable())
			->where($resource->getKeyName(), $resource->getKey())
			->update(['filesize' => $filesize]);

		$resource->setAttribute('filesize', $filesize);
		$resource->syncOriginalAttribute('filesize');

		return TRUE;
	}

	public function calculate(Resource $resource): ?int {
		if ($resource instanceof Text) {
			return strlen((string)$resource->getContent());
		}

		if (!$resource instanceof File || !$resource->hasLocalFile()) {
			return NULL;
		}

		try {
			return $resource->getLocalDisk()->size($resource->getLocalFilePath());
		} catch (FileNotFoundException|UnableToRetrieveMetadata $e) {
			Log::warning('Unable to retrieve local file metadata.', [
				'resource_id' => $resource->getKey(),
				'metadata'    => 'file_size',
			]);

			return NULL;
		}
	}

	public static function fileTypeKeys(): array {
		return array_keys(array_filter(
			Resource::getSingleTableTypeMap(),
			fn (string $class): bool => is_a($class, File::class, TRUE)
		));
	}
}
