<?php

namespace App\Services\ResourceHandling;

use App\Models\DocumentFile;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Generators\DocumentPreviewGenerator;
use App\Services\ResourceHandling\Exceptions\InvalidPageNoException;
use Illuminate\Support\Facades\Log;

class DocHandlingService {


	protected $fileHandlingService;
	protected $pdfHandlingService;

	public function __construct(FileHandlingService $fileHandlingService, PdfHandlingService $pdfHandlingService) {
		$this->fileHandlingService = $fileHandlingService;
		$this->pdfHandlingService  = $pdfHandlingService;
	}

	/**
	 * @param DocumentFile $resource
	 * @return DocumentFile
	 */
	public function countDocPages(DocumentFile $resource) {

		if ($resource->hasLocalFile()) {
			$generator = $resource->getPreviewGenerator();

			if (!$generator instanceof DocumentPreviewGenerator || !$generator->imagePreviewAble($resource)) {

				$resource->page_count = FALSE;
				Log::error('Could not count the page number because there is no fitting generator',
					['resource' => $resource->toArray()]);

			} else {

				try {
					$localPdfPath         = $generator->getTemporaryPdfFromDocument($resource);
					$pages                = $this->pdfHandlingService->countPdfPagesInFilepath($localPdfPath);
					$resource->page_count = (int)$pages;
				} catch (NotPreviewAbleException $e) {
					$resource->page_count = FALSE;
				} catch (InvalidPageNoException $e) {
					$resource->page_count = FALSE;
				}

			}

		} else {
			$resource->page_count = FALSE;
		}

		$resource->save();

		return $resource;
	}


	public function documentResourceToText(DocumentFile $resource, $fromPage = NULL, $toPage = NULL) {

		$generator = $resource->getPreviewGenerator();

		if ($generator instanceof DocumentPreviewGenerator) {
			try {
				$localPdfPath = $generator->getTemporaryPdfFromDocument($resource);
				$text         = $this->pdfHandlingService->pdfToText($localPdfPath, $fromPage, $toPage);

				return $text;
			} catch (NotPreviewAbleException $e) {
			}
		}

		return '';
	}


}