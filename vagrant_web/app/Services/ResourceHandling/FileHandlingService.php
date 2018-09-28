<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\ResourceHandling;


use App\Models\File;
use App\Models\Resource;
use App\Services\ResourceHandling\Exceptions\LocalFileDoesNotExistException;
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

	/**
	 * @param \App\Models\Resource $resource
	 */
	public function deleteResourceCompletely(Resource $resource) {

		// Delete Files from Disk
		if ($resource instanceof File) {
			$resource->deleteLocalFile();
		}

		// Delete in DB
		$resource->delete();

	}


	/**
	 * @param File $resource
	 * @return string
	 * @throws LocalFileDoesNotExistException
	 * @throws RemoteFileDoesNotExistException
	 */
	public function getLocalFilePath(File $resource) {

		// Todo: Check Authorization


		if ($resource->hasLocalFile()) {

			$path = $resource->getAbsoluteLocalPath();

			if ($path !== FALSE) {
				return $path;
			}


			throw new LocalFileDoesNotExistException();

		} else if ($resource->hasRemoteFile()) {
			// Todo: Copy file to local Destination
			die('Not implemented yet');
		}

		throw new RemoteFileDoesNotExistException();
	}


}