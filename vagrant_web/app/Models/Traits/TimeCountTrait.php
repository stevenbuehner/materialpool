<?php

namespace App\Models\Traits;

/**
 * Trait PageCountTrait
 *
 * @package App\Models\Traits
 * @property $time_count
 */
trait TimeCountTrait {

	protected static $TIME_COUNT_KEY = 'timeCount';

	public function setLocalPathAttribute($path) {
		if ($path !== $this->local_path) {
			parent::setLocalPathAttribute($path);
			$this->removeOption(self::$TIME_COUNT_KEY);
		}
	}

	public function setRemotePathAttribute($path) {
		if ($path !== $this->remote_path) {
			parent::setRemotePathAttribute($path);
			$this->removeOption(self::$TIME_COUNT_KEY);
		}
	}

	/**
	 * @return int|NULL
	 */
	public function getTimeCountAttribute() {
		return $this->getOption(self::$TIME_COUNT_KEY, NULL);
	}

	/**
	 * @param int|NULL $pageCount
	 */
	public function setTimeCountAttribute($pageCount) {
		$this->setOption(self::$TIME_COUNT_KEY, $pageCount);
	}

	protected function setupTimeCountAttribute() {
		$this->append('time_count');
	}


}
