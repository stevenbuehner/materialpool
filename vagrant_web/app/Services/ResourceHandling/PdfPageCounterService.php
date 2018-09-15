<?php

namespace App\Services\ResourceHandling;

use App\Models\PdfFile;

class PdfPageCounterService {

	/**
	 * @param PdfFile $resource
	 * @return PdfFile
	 */
	public function countPdfPages(PdfFile $resource) {

		if ($resource->hasLocalFile() && $localPdfPath = $resource->getAbsoluteLocalPath()) {

			try {
				$im = new \Imagick();
				$im->pingImage($localPdfPath);
				$pageCountCache       = (int) $im->getNumberImages();
				$resource->page_count = $pageCountCache;
			} catch (\ImagickException $e) {
				$resource->page_count = FALSE;
			}

			$resource->save();
		}

		return $resource;

	}

}