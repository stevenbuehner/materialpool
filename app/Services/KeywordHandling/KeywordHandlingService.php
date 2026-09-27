<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\KeywordHandling;


use App\Models\Keyword;
use App\Models\Material;
use App\Models\User;
use App\Services\KeywordHandling\Exceptions\InvalidKeywordTypeException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KeywordHandlingService {

	/**
	 * @param Keyword $keyword
	 * @param         $targetType
	 * @return Keyword|null
	 * @throws InvalidKeywordTypeException
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

		// Todo Do a single update to update all at once
		// DB:: Delete dupliactes and then -> 	DB::update('')


		// Update each Material
		$sM->each(function (Material $material) use ($main, $second) {

			$relevance = $material->pivot->relevance;
			$material->keywords()->detach($second->id);
			$material->keywords()->syncWithoutDetaching([$main->id => ['relevance' => $relevance]]);

		});

		// $materialWithAuthor = Material::where('author_id', '=', $second->id)->get();
		$mm = new Material();
		DB::table($mm->getTable())
			->where('author_id', $second->id)
			->update(['author_id' => $main->id]);


		// Move all Child-Keywords to the $main as children - otherwise they will be deleted
		$children = $second->children;
		$children->each(function ($childKeyword) use ($main) {
			$childKeyword->parent_id = $main->id;
			$childKeyword->save();
		});

		$second->delete();

		return $main;

	}

	/**
	 * @param Keyword $keyword
	 * @return bool
	 */
	public function isKeywordUsedByMaterials(Keyword $keyword) {

		// Test for material-keyword-relationship (other materials use this keyword)
		$keywordHasMaterial = Keyword::has('materials')
			->where('id', '=', $keyword->id)
			->take(1)
			->get()
			->count();

		return $keywordHasMaterial !== 0;

	}

	/**
	 * @param Keyword $keyword
	 * @return bool
	 */
	public function isKeywordUsedAsMaterialAuthor(Keyword $keyword) {

		// Test for material-author-relationship (Keyword used as author)
		$keywordInAuthor = Material::has('author')
			->where('author_id', '=', $keyword->id)
			->take(1)
			->get()
			->count();

		return $keywordInAuthor !== 0;

	}

	public function deleteKeyword(Keyword $keyword, $withChildren = FALSE) {

		if ($withChildren === FALSE) {
			// 2) Rette alle Kinder und Kindeskinder aus dem Baum
			$this->extractAndPreserveAllKeywordChildren($keyword);
		}

		// 3a) Lösche alle Keyword Assoziationen
		$numDetached = $keyword->materials()->detach();

		// 3b) Todo: Lösche ggf. icons zu dem Keyword

		// 3c) Lösche Author-Zuordnungen zu Materialien
		/** @var Material[] $materials */
		$materials = $keyword->materialAuthors;
		foreach ($materials as $m) {
			$m->author()->dissociate()->save();
		}

		if ($withChildren === TRUE) {
			// Rekursiv alle Keywords-Kinder im Baum löschen
			// $allDescendants    = $keyword->descendants;
			$allDirectChildren = $keyword->children;

			foreach ($allDirectChildren as $childKeyword) {
				$this->deleteKeyword($childKeyword, $withChildren);
			}
		}

		// 4) Lösche das Keyword selbst
		$keyword->delete();
		Log::info('Keyword deleted', $keyword->toArray());

	}

	/**
	 * @param Keyword $keyword
	 * @return Keyword
	 */
	public function extractAndPreserveAllKeywordChildren(Keyword $keyword) {

		// Nehme das Keyword aus allen Parent-Child Funktionen heraus
		/** @var Keyword[] $allChildren */
		$allChildren = $keyword->children;

		foreach ($allChildren as $childKeyword) {
			// Implicit save
			$childKeyword->insertAfterNode($keyword);
		}

		$keyword->load('children');

		return $keyword;
	}

	/**
	 * @param Keyword $keyword
	 * @param User $otherThan
	 * @return bool
	 */
	public function isKeywordUsedByOtherUsersThan(Keyword $keyword, User $otherThan) {

		// Prüfe ob das Keyword in Materialien vorkommt, worauf dieser Nutzer keine Rechte hat ==> Abbruch mit Fehlermeldung
		$materialsByOthers = $keyword->materials()
			->where('materials.created_by', '!=', $otherThan->id)
			->count();

		return $materialsByOthers > 0;

	}


}