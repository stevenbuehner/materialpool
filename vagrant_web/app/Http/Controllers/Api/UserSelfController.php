<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class UserSelfController extends BaseController {

	public function __construct() {
		$this->middleware('auth:api');
	}

	public function index() {
		return Auth::user()->toArray();
	}

	public function storeSettings(User $user, Request $request) {

		$request->validate([
			'data' => 'array'
		]);

		$user->frontend_user_settings = $request->get('data', []);
		$user->save();

		return $user->toArray();

	}

}
