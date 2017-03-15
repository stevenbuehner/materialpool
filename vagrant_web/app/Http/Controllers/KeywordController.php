<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use Illuminate\Http\Request;
use Kalnoy\Nestedset\Collection;

class KeywordController extends Controller {
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		//
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
	 * @param  Keyword $keyword
	 * @return \Illuminate\Http\Response
	 */
	public function show(Keyword $keyword) {

		/** @var Collection $keywords */
		$keywords = $keyword->descendants;
		$keywords->prepend($keyword);
		$materials = $keyword->descendantMaterials()->with(['keywords', 'resources'])->paginate(20);

		return view('materials.listing', compact('materials', 'keywords'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *^
	 *
	 * @param  Keyword $keyword
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Keyword $keyword) {
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Keyword                  $keyword
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Keyword $keyword) {
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  Keyword $keyword
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Keyword $keyword) {
		//
	}
}
