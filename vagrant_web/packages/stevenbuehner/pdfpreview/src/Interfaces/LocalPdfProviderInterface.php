<?php

namespace StevenBuehner\PdfPreview\Interfaces;

interface LocalPdfProviderInterface {

	/**
	 * @param $fileId
	 * @return string path
	 */
	public function getLocalPdfPath($fileId);

	public function cleanupPdf($fileId);
}