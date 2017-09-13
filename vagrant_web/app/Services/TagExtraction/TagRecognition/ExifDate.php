<?php

namespace App\Services\TagExtraction\TagRecognition;

use App\Services\TagExtraction\Interfaces\PropertyInterface;
use App\Services\TagExtraction\Properties\CreateDateProperty;

class ExifDate extends Created {

	public function __construct() {
		$this->setPriority(80);
	}

	/**
	 * @param String $stringValue
	 * @return PropertyInterface[]
	 */
	public function extractSpecializedTag($stringValue) {
		$tagValue = $this->hasPrefix([
										 'CreateDate',
									 ], $stringValue);

		$result = [];

		if (FALSE !== $tagValue) {
			$tagValue = $this->recognizeStringFromDate($tagValue);

			if ($tagValue !== FALSE) {
				/** @var $tagValue \DateTime */
				$result[] = new CreateDateProperty($tagValue); // Don't set a priority, because we don't know the source of our guessed information
			}
		}

		return $result;
	}

	/**
	 * @param string $str
	 * @return string
	 */
	private function recognizeStringFromDate($str) {
		$d = FALSE;

		// First match german date
		/*
		 * Recognize:
		 * 2012:06:07
		 * 2012:06:07 13:01:42
		 * 2012:06:07 13:01:42.21+01:00
		 * 2012:06:07 13:01:42+01:00
		 * 2012:06:07 13:01:42Z
		 */
		if (preg_match('~^(\d{4}):(0\d|1[012]|\d):(31|30|[012]\d|\d)(\s+\d{2}:\d{2}:\d{2}(\s+\+\d{2}:\d{2})?(Z|(\.\d+)\+[0-9:]+)?)?$$~',
					   $str,
					   $matches) === 1
		) {
			$d = new \DateTime();
			$d->setDate($matches[1], $matches[2], $matches[3]);
		}

		if ($d instanceof \DateTime && $this->isValidDate($d) === FALSE) {
			$d = FALSE;
		}

		return $d;
	}

	public function allowOtherRecognitionsOnSuccess() {
		return FALSE;
	}

}

?>