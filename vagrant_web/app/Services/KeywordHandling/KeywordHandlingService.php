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
	 * @param Keyword $keyword
	 * @param         $targetType
	 * @throws InvalidKeywordTypeException
	 * @return Keyword|null
	 */
	public function changeKeywordType(Keyword $keyword, $targetType) {

		$test        = new Keyword();
		$test->type  = $targetType;
		$test->title = $keyword->title;

		// Check existance first
		$existsAlready = Keyword::where([
											['type', '=', $test->type],
											['title', '=', $test->title]
										])->first();

		if ($existsAlready) {
			$keyword = $this->mergeKeywords($existsAlready, $keyword);
		} else {

			$lc_title = $test->lc_title;

			DB::table($keyword->getTable())
			  ->where($keyword->getKeyName(), $keyword->getKey())
			  ->update(['type' => $test->type, 'lc_title' => $test->lc_title]);

			$keyword = $keyword->fresh();
		}

		return $keyword;
	}

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

		// Update each Material
		$sM->each(function (Material $material) use ($main, $second) {

			$relevance = $material->pivot->relevance;
			$material->keywords()->detach($second->id);
			$material->keywords()->syncWithoutDetaching([$main->id => ['relevance' => $relevance]]);

		});


		// Move all Child-Keywords to the $main as children - otherwise they will be deleted
		$children = $second->children;
		$children->each(function ($childKeyword) use ($main) {
			$childKeyword->parent_id = $main->id;
			$childKeyword->save();
		});

		$second->delete();

		return $main;

	}


}