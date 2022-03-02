<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Keyword;
use Illuminate\Support\Facades\DB;


class KeywordSuggestionsController extends BaseController {


	public function __construct() {
	}

	public function getKeywordSuggestions(Keyword $keyword) {

// 		$ancestors = $keyword->ancestors;
		$response = $this->getKeywordsOtherMaterialsUse($keyword)
			->with(['ancestors', 'descendants'])
			->paginate()
			->appends('relevance');

		return $response;
		// return $bv->bibleverseCrossReferencesQuery()->paginate($perPage);

	}

	protected function getKeywordsOtherMaterialsUse(Keyword $keyword) {

		$query = $keyword->newQuery()
			->select(['k2.*', DB::raw('count(`k2`.`id`) as `relevance`')])
			->join('keyword_material as km1', $keyword->qualifyColumn('id'), '=', 'km1.keyword_id')
			->join('keyword_material as km2', 'km1.material_id', '=', 'km2.material_id')
			->join('keywords as k2', 'km2.keyword_id', '=', 'k2.id')
			->where($keyword->qualifyColumn('id'), '=', $keyword->id)
			->where('k2.id', '!=', $keyword->id)
			->groupBy('k2.id')
			->orderBy('relevance', 'desc');

		return $query;

	}


}
