<?php

namespace App\Models\Traits;

/**
 * Trait PageCountTrait
 *
 * @property $page_count
 */
trait PageCountTrait {

	protected static $PAGE_COUNT_KEY = 'pdfPageCount';

	public function setLocalPathAttribute($path) {
		if ($path !== $this->local_path) {
			parent::setLocalPathAttribute($path);
			$this->removeOption(self::$PAGE_COUNT_KEY);
		}
	}

	public function setRemotePathAttribute($path) {
		if ($path !== $this->remote_path) {
			parent::setRemotePathAttribute($path);
			$this->removeOption(self::$PAGE_COUNT_KEY);
		}
	}

	/**
	 * @return int|NULL
	 */
	public function getPageCountAttribute() {
		return $this->getOption(self::$PAGE_COUNT_KEY, NULL);
	}

	/**
	 * @param int|NULL $pageCount
	 */
	public function setPageCountAttribute($pageCount) {
		$this->setOption(self::$PAGE_COUNT_KEY, $pageCount);
	}

	protected function setupPageCountAttribute() {
		$this->append('page_count');
	}


}
