<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Bibleverse;
use App\Models\Material;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;


class BibleverseController extends BaseController {

	protected $bibleVerseService;

	public function __construct(BibleVerseService $bibleVerseService) {
		$this->bibleVerseService = $bibleVerseService;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request) {

		$search_term       = $request->input('q');
		$bibleverseGuesses = collect($this->bibleVerseService->stringToBibleVerse($search_term));
		$query             = Bibleverse::findWhereInRange($bibleverseGuesses);

		return $query->paginate(20);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request) {

	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		/** @var BibleVerseService $bibleVerseService */

		$bibleverse = NULL;

		if ($request->has('from') && $request->has('to')) {
			$bibleverse = Bibleverse::firstOrCreate([
														'from' => $request->get('from'),
														'to'   => $request->get('to')
													]);
		}

		if ($bibleverse === NULL && $request->has('label')) {
			$bibleVerseService = resolve('BibleVerseService');

			$recognizedVerses = $bibleVerseService->stringToBibleVerse($request->get('label', ''));

			if (count($recognizedVerses) > 0) {
				$bibleverse = Bibleverse::findOrCreateFromBibleverseInterface($recognizedVerses[0]);
			}
		}

		// Reload from DB to assign parent_id, icon etc. to the model
		// $bibleverse = $bibleverse->fresh();

		return $bibleverse;
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Models\Bibleverse $bibleverse
	 * @return \Illuminate\Http\Response
	 */
	public function show(Bibleverse $bibleverse) {
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  \App\Models\Bibleverse $bibleverse
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Bibleverse $bibleverse) {
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \App\Models\Bibleverse   $bibleverse
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Bibleverse $bibleverse) {
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Models\Bibleverse $bibleverse
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Bibleverse $bibleverse) {
		//
	}

	public function createOrUpdateAssignment(Material $material, Bibleverse $bibleverse, Request $request) {


		// Create or Update Relationship without detaching others
		$material->bibleverses()->sync(
			[
				$bibleverse->id =>
					[
						'relevance' => $request->get('relevance',
													 RelevanceInterface::RELEVANCE_USER_AVG)
					]
			],
			$doNotDetachOtherRelationships = FALSE);

		return $material->bibleverses()->where('bibleverses.id', '=', $bibleverse->id)->get()->first();
	}

	public function deleteAssignment(Material $material, Bibleverse $bibleverse) {
		return $material->bibleverses()->detach($bibleverse);
	}
}
