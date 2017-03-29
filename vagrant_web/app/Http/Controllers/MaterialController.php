<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller {

	protected static $withAttributes = ['keywords', 'bibleverses', 'resources'];

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		$materials = Material::with(self::$withAttributes)->orderBy('updated_at')->paginate(50);

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
		$material->load(self::$withAttributes);

		return view('materials.show', ['material' => $material]);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  Material $material
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Material $material) {

	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Material                 $material
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Material $material) {
		//
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
