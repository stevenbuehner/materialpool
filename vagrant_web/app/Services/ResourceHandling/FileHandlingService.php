<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\ResourceHandling;


use App\Events\ResourceWasDeleted;
use App\Models\File;
use App\Models\Resource;
use App\Services\ResourceHandling\Exceptions\LocalFileDoesNotExistException;
use App\Services\ResourceHandling\Exceptions\RemoteFileDoesNotExistException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileHandlingService extends ResourceHandlingService {

	public function copyRemoteFileToLocalStorage(File $resource) {
		if (!$resource->hasRemoteFile()) {
			throw new RemoteFileDoesNotExistException();
		}

		$this->cleanUpFileResource($resource);

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

	public function cleanUpFileResource(File $resource) {

		if ($resource->hasLocalFile()) {
			try {
				$resource->deleteLocalFile();

				Log::info('Cleaned up local file of Resource (ID: ' . $resource->id . ')');

			} catch (\Exception $e) {
				Log::error('Error while cleanup / deleting local file', [
					'message' => $e->getMessage(),
					'trace'   => $e->getTrace()
				]);
			}
		}

	}


	/**
	 * @param \App\Models\Resource $resource
	 */
	public function deleteResourceCompletely(Resource $resource) {

		$resource->load(['materials', 'foreignIds']);

		$this->detachAllMaterials($resource);
		$this->detachAllForeignIds($resource);

		// Delete Files from Disk
		if ($resource instanceof File) {
			$this->cleanUpFileResource($resource);
		}

		// Delete in DB
		$resource->delete();
		event(new ResourceWasDeleted($resource));

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