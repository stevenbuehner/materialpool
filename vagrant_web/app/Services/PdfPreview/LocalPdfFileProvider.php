<?php
/**
 * This file was created by  steven
 * Created: 22.08.17 12:14
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PdfPreview;


use App\Models\DocumentFile;
use App\Models\PdfFile;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use StevenBuehner\PdfPreview\Exceptions\FileWasNotRetrieveableException;
use StevenBuehner\PdfPreview\Interfaces\LocalPdfProviderInterface;
use StevenBuehner\PdfPreview\Interfaces\path;

class LocalPdfFileProvider implements LocalPdfProviderInterface {

	/**
	 * @param $fileId
	 * @return string path
	 * @throws FileWasNotRetrieveableException
	 */
	public function getLocalPdfPath($fileId) {

		// Todo: Check Authorization

		/** @var DocumentFile $documentResource */
		$documentResource = PdfFile::findOrFail($fileId);


		if ($documentResource->hasLocalFile()) {
			$disk = $documentResource->getLocalDisk();
			$path = $documentResource->getLocalFilePath();

			if ($disk->getDriver() instanceof Filesystem) {
				/** @var AdapterInterface $adapter */
				$adapter = $disk->getDriver()->getAdapter();

				if ($adapter instanceof Local) {
					return $adapter->applyPathPrefix($path);
				}
			}

			// Todo: Copy file to local Destination

			return '';

		}

		throw new FileWasNotRetrieveableException();
	}

	public function cleanupPdf($fileId) {
		// TODO: Implement cleanupPdf() method.
	}
}