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

	static $icon = 'properties/bibleverse.svg';
	static $type = 'bibleverse';

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
		$bibelverseModel = Bibleverse::findOrCreateFromBibleverseInterface($this->getValue());
		$relevance       = $this->getRelevance();

		$foundInstance = $material->bibleverses->where('id', $bibelverseModel->id);

		if ($foundInstance) {
			# Only update pivot

			if (empty($foundInstance->pivot->relevance) || $foundInstance->pivot->relevance < $relevance) {
				$foundInstance->pivot->relevance = $relevance;
				$foundInstance->pivot->save();
				// $material->keywords()->updateExistingPivot($foundInstance->id, $relevance);
			}

		} else {
			// Insert a new Instance
			$material->bibleverses()->attach($bibelverseModel->id, ['relevance' => $relevance]);
			// $material->keywords()->attach($newKeyword, ['relevance' => $relevance]);
		}
	}

}