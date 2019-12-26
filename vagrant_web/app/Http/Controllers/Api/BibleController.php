<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Bible;
use Illuminate\Http\Request;

class BibleController extends BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		return Bible::orderBy('usage_priority', 'DESC')->paginate(50);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return void
	 * @throws \Exception
	 */
	public function store(Request $request) {
		throw new \Exception('Not implemented yet');
	}

	/**
	 * Display the specified resource.
	 *
	 * @param \App\Models\Bible $bible
	 * @return Bible
	 */
	public function show(Bible $bible) {
		return $bible;
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \App\Models\Bible $bible
	 * @return void
	 * @throws \Exception
	 */
	public function update(Request $request, Bible $bible) {
		throw new \Exception('Not implemented yet');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param \App\Models\Bible $bible
	 * @return void
	 * @throws \Exception
	 */
	public function destroy(Bible $bible) {
		throw new \Exception('Not implemented yet');
	}
}
