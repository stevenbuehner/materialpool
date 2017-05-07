<?php
/**
 * This file was created by  steven
 * Created: 30.08.16 14:58
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\TagExtraction\Properties;

use App\Models\Bibleverse;
use App\Models\Material;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;

class BibleverseProperty extends Property {

	/**
	 * BibleverseProperty constructor.
	 *
	 * @param BibleVerseInterface $bibleVerse
	 * @param int                 $relevance
	 */
	public function __construct(BibleVerseInterface $bibleVerse, int $relevance = 0) {
		parent::__construct($bibleVerse, $relevance);
	}


	/**
	 * The function has to insert it's own value into the item
	 *
	 * @param Material $material
	 */
	function insertYourselfToItem(Material $material) {
		$bibelverseModel = $this->getBibleVerseValue();
		$bibelverseModel->save();
		$relevance = $this->getRelevance();
		//$material->load('bibleverses');

		$foundInstance = $material->bibleverses()->where('bibleverse_id', $bibelverseModel->id)->get();

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
			$material->bibleverses()->attach($bibelverseModel, ['relevance' => $relevance]);
		}
	}

	public function getBibleVerseValue() {
		return Bibleverse::findOrNewFromBibleverseInterface($this->getValue());
	}

}