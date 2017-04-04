<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Requests\KeywordRequest;
use App\Models\Keyword;
use App\Models\Material;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use Illuminate\Http\Request;


class KeywordController extends BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 */
	public function index(Request $request) {

		$search_term = $request->input('q');
		$search_type = $request->input('t');


		$query = Keyword::query();

		if ($search_term) {
			$query->where('title', 'LIKE', '%' . $search_term . '%');
		}

		if ($search_type) {
			$query->where('type', '=', $search_type);
		}


		return $query->paginate(20);
	}

	public function show(Keyword $keyword) {
		return $keyword;
	}

	public function createAssignment(Material $material, Keyword $keyword = NULL, Request $request) {
		$relevance = $request->get('relevance', RelevanceInterface::RELEVANCE_USER_MIN);

		// Try to create the keyword
		if ($keyword == NULL) {
			/** @var KeywordRequest $kw */
			$kw = KeywordRequest::createFromBase($request);
			$kw->validate();

			$keyword = $this->create($kw);
		}

		$material->keywords()->attach($keyword, ['relevance' => $relevance]);

		return $material->keywords()->where('keywords.id', '=', $keyword->id)->get()->first();
	}

	public function create(KeywordRequest $keywordRequest) {
		$type  = $keywordRequest->get('type', Keyword::getSingleTableType());
		$class = Keyword::getSingleTableTypeMap()[$type];

		/** @var Keyword $keyword */
		$keyword = new $class($keywordRequest->all());
		$keyword->save();

		return $keyword;
	}

	public function updateAssignment(Material $material, Keyword $keyword, Request $request) {
		$material->keywords()->updateExistingPivot($keyword->id, ['relevance' => $request->get('relevance')]);

		return $material->keywords()->where('keywords.id', '=', $keyword->id)->get()->first();
	}

	public function deleteAssignment(Material $material, Keyword $keyword) {
		return $material->keywords()->detach($keyword);
	}


}
