<?php

namespace StevenBuehner\PdfPreview\Interfaces;

interface LocalPdfProviderInterface {

	/**
	 * @param $fileId
	 * @return path
	 */
	public function getLocalPdfPath($fileId);

	public function cleanupPdf($fileId);
}