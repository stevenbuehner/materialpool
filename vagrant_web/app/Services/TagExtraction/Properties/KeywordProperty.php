<?php
/**
 * This file was created by  steven
 * Created: 30.08.16 14:58
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\TagExtraction\Properties;

use App\Models\Keyword;
use App\Models\Material;

class KeywordProperty extends Property {

	public function __construct($title, $type, $relevance = 0) {
		parent::__construct([$type, $title], $relevance);
	}

	/**
	 * The function has to insert it's own value into the item
	 *
	 * @param Material $material
	 */
	function insertYourselfToItem(Material $material) {

		$newKeyword = $this->getKeywordValue();
		$relevance  = $this->getRelevance();

		$newKeyword->save();
		$foundInstance = $material->keywords()->where('keyword_id', $newKeyword->id)->get();

		if ($foundInstance->count() > 0) {
			# Only update pivot
			$foundInstance = $foundInstance->first();

			if (empty($foundInstance->pivot->relevance) || $foundInstance->pivot->relevance < $relevance) {
				$foundInstance->pivot->relevance = $relevance;
				$foundInstance->pivot->save();
				// $material->keywords()->updateExistingPivot($foundInstance->id, $relevance);
			}

		} else {
			// Insert a new Instance
			$material->keywords()->attach($newKeyword, ['relevance' => $relevance]);
		}
	}

	public function getKeywordValue() {
		list($type, $title) = parent::getValue();
		$newKeyword = Keyword::firstOrNew([
			'title' => $title,
			'type'  => $type
		]);

		return $newKeyword;
	}

	public function getValue() {
		list($type, $title) = parent::getValue();

		return $title;
	}


	public function __toString() {
		return 'r=' . $this->getRelevance() . ',v=' . json_encode(parent::getValue());
	}
}