<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\ResourceHandling;


use App\Models\File;
use App\Services\ResourceHandling\Exceptions\RemoteFileDoesNotExistException;
use Illuminate\Support\Facades\Storage;

class FileHandlingService {

	public function copyRemoteFileToLocalStorage(File $resource) {
		if (!$resource->hasRemoteFile()) {
			throw new RemoteFileDoesNotExistException();
		}

		if ($resource->hasLocalFile()) {
			$resource->deleteLocalFile();
		}

		$stream = $resource->getRemoteFileStream();

		if (!is_resource($stream)) {
			throw new RemoteFileDoesNotExistException();
		}

		$disk    = Storage::disk(config('app.disks.resources'));
		$newPath = $resource->created_by . DIRECTORY_SEPARATOR . $resource->type . DIRECTORY_SEPARATOR . uniqid();
		$disk->writeStream($newPath, $stream);
		fclose($stream);


		// TODO: Unfinished! Stopped here!
// 		$resource->setLocalPath

	}


}