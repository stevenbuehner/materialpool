<?php

namespace App\Services\TagExtraction\TagRecognition;

use App\Models\Place as PlaceModel;
use App\Services\TagExtraction\AbstractTagRecognition;
use App\Services\TagExtraction\Properties\KeywordProperty;

class Place extends AbstractTagRecognition {

	public function __construct() {
		$this->setPriority(40);
	}

	/**
	 * @param String $stringValue
	 * @return PlaceModel[]
	 */
	public function extractSpecializedTag($stringValue) {
		$tagValue = $this->hasPrefix([
										 'place',
										 'ort',
										 'city',
										 'stadt'
									 ], $stringValue);

		$result = [];

		if (FALSE !== $tagValue) {
			$result[] = new KeywordProperty($tagValue, PlaceModel::class);
		}

		return $result;
	}

	public function allowOtherRecognitionsOnSuccess() {
		return FALSE;
	}

}

?>