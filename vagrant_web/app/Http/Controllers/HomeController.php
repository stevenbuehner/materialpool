<?php

namespace App\Http\Controllers;

class HomeController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth:web');
	}

	public function index() {
		return view('vuerouter/index');
	}

	public function keepAlive() {
		return response()->json(['ok' => TRUE]);
	}

}