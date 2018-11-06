<?php

namespace App\Services\TagExtraction\TagRecognition;


use App\Services\TagExtraction\AbstractTagRecognition;
use App\Services\TagExtraction\Properties\AuthorProperty;
use App\Services\TagExtraction\Properties\KeywordProperty;
use App\Services\TagExtraction\Properties\Property;

class Author extends AbstractTagRecognition {

	public function __construct() {
		$this->setPriority(50);
	}

	/**
	 * @param String $stringValue
	 * @return Property[]
	 */
	public function extractSpecializedTag($stringValue) {
		$tagValue = $this->hasPrefix([
										 'von',
										 'from',
										 'author'
									 ], $stringValue);

		$result = [];

		if (FALSE !== $tagValue) {
			if (preg_match('~^[0-9.:!-]+~i', $tagValue) == 0) {
				// Don't use names starting with numbers or strange chars
				$result[]  = new KeywordProperty($tagValue, 'person');
				$result [] = new AuthorProperty($tagValue);  // Don't set a priority, because we don't know the source of our guessed information
			}
		}

		return $result;
	}

	public function allowOtherRecognitionsOnSuccess() {
		return FALSE;
	}

}