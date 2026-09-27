<?php

namespace App\Services\TagExtraction\Interfaces;

interface CompareablePropertyInterface {

	/**
	 * Returns a string, that represents the value of the instance to compare it with other instances
	 *
	 * @return string
	 */
	public function getCompareString();
}