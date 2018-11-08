<?php

namespace App\Models;

use App\Jobs\CalculatePdfPageSize;
use App\Services\PreviewGeneration\Generators\PdfPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;

class PdfFile extends File {

	const PAGE_COUNT_KEY = 'pdfPageCount';
	protected static $singleTableType = 'pdf';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		$this->append('page_count');
	}

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
		if ($path !== $this->local_path) {
			parent::setLocalPathAttribute($path);
			$this->removeOption(self::PAGE_COUNT_KEY);
		}
	}

	public function setRemotePathAttribute($path) {
		if ($path !== $this->remote_path) {
			parent::setRemotePathAttribute($path);
			$this->removeOption(self::PAGE_COUNT_KEY);
		}
	}


	/**
	 * @return int|NULL
	 */
	public function getPageCountAttribute() {
		return $this->getOption(self::PAGE_COUNT_KEY, NULL);
	}

	/**
	 * @param int|NULL $pageCount
	 */
	public function setPageCountAttribute($pageCount) {
		$this->setOption(self::PAGE_COUNT_KEY, $pageCount);
	}

	public function getPostCreateJobs() {
		return array_merge(parent::getPostCreateJobs(), [new CalculatePdfPageSize($this)]);
	}


}
