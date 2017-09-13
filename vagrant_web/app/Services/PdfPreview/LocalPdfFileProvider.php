<?php
/**
 * This file was created by  steven
 * Created: 22.08.17 12:14
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PdfPreview;


use App\Models\DocumentFile;
use App\Models\PdfFile;
use StevenBuehner\PdfPreview\Exceptions\FileWasNotRetrieveableException;
use StevenBuehner\PdfPreview\Interfaces\LocalPdfProviderInterface;

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

			$path = $documentResource->getAbsoluteLocalPath();

			if ($path !== FALSE) {
				return $path;
			}

			// Todo: Copy file to local Destination

			return '';

		} else if ($documentResource->hasRemoteFile()) {

		}

		throw new FileWasNotRetrieveableException();
	}

	public function cleanupPdf($fileId) {
		// TODO: Implement cleanupPdf() method.
	}
}