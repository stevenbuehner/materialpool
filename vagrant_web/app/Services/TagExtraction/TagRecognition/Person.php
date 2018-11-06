<?php

namespace App\Services\TagExtraction\TagRecognition;

use App\Services\TagExtraction\AbstractTagRecognition;
use App\Services\TagExtraction\Properties\KeywordProperty;

class Person extends AbstractTagRecognition {

	public function __construct() {
		$this->setPriority(40);
	}

	/**
	 * @param String $stringValue
	 * @return KeywordProperty[]
	 */
	public function extractSpecializedTag($stringValue) {
		$tagValue = $this->hasPrefix([
										 'person'
									 ], $stringValue);

		$result = [];

		if (FALSE !== $tagValue) {
			$result[] = new KeywordProperty($tagValue, 'person');
		}

		return $result;
	}

	public function allowOtherRecognitionsOnSuccess() {
		return FALSE;
	}

}