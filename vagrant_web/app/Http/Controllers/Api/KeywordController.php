<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Keyword;
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


}
