<?php

namespace App\Models;

use App\Services\PreviewGeneration\Generators\PdfPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;

class PdfFile extends File {

	const PAGE_COUNT_KEY = 'pdfPageCount';
	protected static $singleTableType = 'pdf';

	public static function getValidationRules() {
		$rules         = parent::getValidationRules();
		$rules['file'] = 'required|file|mimes:pdf';

		return $rules;
	}

	/**
	 * @param $size
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator($size = 'large') {
		return resolve(PdfPreviewGenerator::class);
	}

	public function setLocalPathAttribute($path) {
		parent::setLocalPathAttribute($path);
		$this->removeOption(self::PAGE_COUNT_KEY);
	}

	public function setRemotePathAttribute($path) {
		parent::setRemotePathAttribute($path);
		$this->removeOption(self::PAGE_COUNT_KEY);
	}

	/**
	 * @return FALSE|int
	 */
	public function getPdfCountAndSaveCache() {
		$pageCountCache = $this->getOption(self::PAGE_COUNT_KEY, FALSE);

		if ($pageCountCache === FALSE) {
			$pageCountCache = $this->countPdfPages();

			if ($pageCountCache !== FALSE) {
				$this->setOption(self::PAGE_COUNT_KEY, $pageCountCache);
				$this->save();
			}
		}

		return $pageCountCache;
	}

	/**
	 * Returns the number of pdf pages the local file has or FALSE on error
	 *
	 * @return FALSE|int
	 */
	protected function countPdfPages() {
		if ($this->hasLocalFile() && $localPdfPath = $this->getAbsoluteLocalPath()) {
			$im = new \Imagick();
			$im->pingImage($localPdfPath);
			$pageCountCache = (int) $im->getNumberImages();

			return $pageCountCache;
		}

		return FALSE;
	}


}
