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

	static $icon = 'properties/keyword.svg';
	static $type = 'keyword';

	public function __construct($title, $class, $relevance = 0) {
		parent::__construct([$class, $title], $relevance);
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
		list($class, $title) = $this->getValue();
		$newKeyword = $class::firstOrNew([
												'title' => $title
											]);

		return $newKeyword;
	}

	public function setKeywordValue($title, $class = Keyword::class) {
		$keyword = $this->getKeywordFromValue($title, $class);
		$this->setValue($keyword);
	}

	public function __toString() {
		return 'r=' . $this->getRelevance() . ',v=' . json_encode($this->getValue());
	}
}