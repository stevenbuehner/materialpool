<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaterialRequest;
use App\Models\Keyword;
use App\Models\Material;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class MaterialController extends Controller {

	protected $withAttributes = [];

	public function __construct() {
		$this->withAttributes = [
			'keywords'    => function ($q) {
				$q->orderBy('keyword_material.relevance', 'desc');
			},
			'bibleverses' => function ($q) {
				$q->orderBy('bibleverse_material.relevance', 'desc');
			},
			'resources'];

	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		$materials = Material::with($this->withAttributes)->orderBy('updated_at')->paginate(50);

		return view('materials.listing', compact('materials'));
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
	 * @param  Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function show(Material $material) {
		$material->load($this->withAttributes);

		return view('materials.show', ['material' => $material]);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Material $material) {
		$material->load($this->withAttributes);


		return view('materials.edit', ['material' => $material]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Material                 $material
	 * @return \Illuminate\Http\Response
	 */
	public function update(MaterialRequest $request, Material $material) {

		if ($request->has('keywords')) {
			$keywordIds = $request->get('keywords', []);

			/** @var Collection $storedKeywords */
			$storedKeywords = $material->keywords;

			// Delete keywords
			$deleteables = $storedKeywords->whereNotIn('id', $keywordIds);
			$t           = $deleteables->pluck('id');
			$material->keywords()->detach($deleteables->pluck('id'));

			// Update existing keyword relevances to at least user value
			$storedKeywords->whereIn('id', $keywordIds)->each(function (Keyword $keyword) {
				$max = max($keyword->pivot->relevance, RelevanceInterface::RELEVANCE_USER_MIN);
				if ($keyword->pivot->relevance != $max) {
					$keyword->pivot->relevance = $max;
					$keyword->pivot->save();
				}
			});

			// Store new keywords
			$storedKeywordsA = $storedKeywords->pluck('id')->toArray();
			$newKeywordIds   = collect($keywordIds)->reject(function ($keywordId) use (&$storedKeywordsA) {
				return in_array($keywordId, $storedKeywordsA);
			})->mapWithKeys(function ($item) {
				return [$item => ['relevance' => RelevanceInterface::RELEVANCE_USER_MAX]];
			});
			$material->keywords()->attach($newKeywordIds->toArray());
		}

		return redirect(route('pool.material.show', $material));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Material $material) {
		//
	}
}
