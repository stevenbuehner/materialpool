<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\KeywordHandling;


use App\Models\Keyword;
use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;

class KeywordHandlingService {

	/**
	 *
	 * Needs $second Keyword to have the relevance Pivot
	 *
	 * @param Keyword $main
	 * @param Keyword $second
	 */
	public function mergeKeywords(Keyword $main, Keyword $second) {

		if ($main->id === $second->id) {
			return $main;
		}

		/** @var Collection $sM */
		$sM = $second->materials;

		//Todo Do a single update to update all at once
		// 	DB:: Delete dupliactes and then -> 	DB::update('')

		$sM->each(function (Material $material) use ($main, $second) {

			$relevance = $material->pivot->relevance;
			$material->keywords()->detach($second->id);
			$material->keywords()->syncWithoutDetaching([$main->id => ['relevance' => $relevance]]);

		});

		$second->delete();

		return $main;

	}


}