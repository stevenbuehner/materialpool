<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\KeywordHandling;


use App\Models\Keyword;
use App\Models\Material;
use App\Services\KeywordHandling\Exceptions\InvalidKeywordTypeException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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

	/**
	 * @param Keyword $keyword
	 * @param         $targetType
	 * @throws InvalidKeywordTypeException
	 */
	public function changeKeywordType(Keyword $keyword, $targetType) {

		$map = Keyword::getSingleTableTypeMap();

		if (!isset($map[$targetType])) {
			throw new InvalidKeywordTypeException();
		}

		DB::table($keyword->getTable())
		  ->where($keyword->getKeyName(), $keyword->getKey())
		  ->update(['type' => $targetType]);

	}


}