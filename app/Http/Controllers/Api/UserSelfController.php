<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\User;
use Illuminate\Http\Request;


class UserSelfController extends BaseController {

	public function __construct() {
		$this->middleware('auth:api');
	}

	public function index(User $user) {
		$this->authorize('view', $user);

		return $user->toArray();
	}

	public function storeSettings(User $user, Request $request) {
		$this->authorize('update', $user);

		$request->validate([
			'data' => 'array'
		]);

		$user->frontend_user_settings = $request->get('data', []);
		$user->save();

		return $user->toArray();

	}

}
