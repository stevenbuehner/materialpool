<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\User;
use Illuminate\Http\Request;


class UserController extends BaseController {

	public function __construct() {
		$this->middleware('auth:api');
	}

	public function index() {
		return User::all()->paginate();
	}

	public function find(Request $request) {

		$search = $request->get('s') ?? '';
		$limit  = (int)$request->get('limit', 50);

		// Add wildcards for search
		if (strlen(trim($search)) === 0) {
			$search = '%';
		} else {
			$search = "%$search%";
		}

		// Limit $limit
		$limit = max(min($limit, 50), 0);

		return User::query()
			->where('name', 'LIKE', $search)
			->where('use_for_mat_usage', '=', TRUE)
			->limit($limit)
			->orderBy('id')
			->get();

	}


}
