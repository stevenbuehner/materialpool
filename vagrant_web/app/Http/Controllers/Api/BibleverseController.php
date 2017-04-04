<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Bibleverse;
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

	}

	public function guess(Request $request) {
		$result      = new Collection();
		$search_term = $request->input('q', '');
		$page        = $request->input('page', 1);
		$bibleVerses = $this->bibleVerseService->stringToBibleVerse($search_term);

		foreach ($bibleVerses as $verse) {
			Bibleverse::findOrNewFromBibleverseInterface($verse);
			$result->push($verse);
		}

		return $result->forPage($page, 20);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		//
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
}
