<?php

namespace App\Services\TagExtraction\TagRecognition;

use App\Models\Keyword;
use App\Models\Place as PlaceModel;
use App\Services\TagExtraction\AbstractTagRecognition;
use App\Services\TagExtraction\Properties\KeywordProperty;

class Beispiel extends AbstractTagRecognition {

	public function __construct() {
		$this->setPriority(0);
	}

	/**
	 * @param String $stringValue
	 * @return PlaceModel[]
	 */
	public function extractSpecializedTag($stringValue) {
		$result = [];

		$tagValue = $this->hasPrefix([
										 'beispiel'
									 ], $stringValue);

		if (FALSE !== $tagValue) {
			$result[] = new KeywordProperty($tagValue, Keyword::class);
			$result[] = new KeywordProperty('Beispiel', Keyword::class);
		}

		return $result;
	}

	public function allowOtherRecognitionsOnSuccess() {
		return FALSE;
	}

}

?>