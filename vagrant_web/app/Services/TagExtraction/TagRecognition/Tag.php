<?php

namespace App\Services\TagExtraction\TagRecognition;


use App\Models\Keyword;
use App\Services\TagExtraction\AbstractTagRecognition;
use App\Services\TagExtraction\Properties\KeywordProperty;

class Tag extends AbstractTagRecognition {

	public function __construct() {
		$this->setPriority(0);
	}

	/**
	 * @param String $stringValue
	 * @return Keyword[]
	 */
	public function extractSpecializedTag($stringValue) {
		$tagValue = $this->hasPrefix([
										 'tag'
									 ], $stringValue);

		$result = [];

		// If ->hasPrefix resultet successfull, use that string. Otherwise continue with $stringValue
		$stringToProcess = $stringValue;
		if (FALSE !== $tagValue) {
			$stringToProcess = $tagValue;
		}

		// Clean up the String
		$stringToProcess = $this->cleanUpString($stringToProcess);

		// Add $stringToProcess to result
		$result[] = new KeywordProperty($stringToProcess, 'key');

		return $result;
	}

	private function cleanUpString($string) {
		$string = str_replace('_', ' ', $string);
		$string = trim($string, "\t .,");

		return $string;
	}

	public function allowOtherRecognitionsOnSuccess() {
		return TRUE;
	}

}