<?php

namespace App\Services\TagExtraction\TagRecognition;

use App\Services\TagExtraction\AbstractTagRecognition;
use App\Services\TagExtraction\Properties\KeywordProperty;

class Beispiel extends AbstractTagRecognition {

	public function __construct() {
		$this->setPriority(0);
	}

	/**
	 * @param String $stringValue
	 * @return KeywordProperty[]
	 */
	public function extractSpecializedTag($stringValue) {
		$result = [];

		$tagValue = $this->hasPrefix([
										 'beispiel'
									 ], $stringValue);

		if (FALSE !== $tagValue) {
			$result[] = new KeywordProperty($tagValue, 'key');
			$result[] = new KeywordProperty('Beispiel', 'key');
		}

		return $result;
	}

	public function allowOtherRecognitionsOnSuccess() {
		return FALSE;
	}

}