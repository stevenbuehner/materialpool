<?php

namespace App\Services\ResourceHandling;

use App\Models\Resource;
use App\Services\ResourceHandling\Exceptions\InvalidResourceTypeException;
use App\Services\TagExtraction\ResourceHandles\TextContentInterface;
use Illuminate\Support\Facades\Storage;

class TextHandlingService extends ResourceHandlingService {

	/**
	 * @param Resource $resource
	 * @return array|void
	 * @throws InvalidResourceTypeException
	 * @throws \League\Flysystem\FileExistsException
	 */
	public function archiveResource(Resource $resource) {

		if (!$resource instanceof TextContentInterface) {
			throw new InvalidResourceTypeException('Expected resource to be type of TextContentInterface');
		}

		$archiveDisc = Storage::disk('archive');
		$filePath    = strftime('%G/%m/%d/') . $resource->id . '.backup_txt';

		$archiveDisc->write($filePath, $resource->getContent());

		return [
			$archiveDisc,
			$filePath
		];
	}

}