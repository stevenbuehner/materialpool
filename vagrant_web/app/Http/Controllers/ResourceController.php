<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResourceController extends Controller {
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {

		// $resources = DB::table('resources')->paginate(15);
		$resources = DB::table('resources')->simplePaginate(15);

		return $resources;
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {
		return view('resource.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) {
		// Mass assignment easy

		$data          = request(['path', 'notes', 'options', 'is_public']);
		$type          = request('type', 'res');
		$classToCreate = Resource::class;


		switch ($type) {
			case 'res':
				$classToCreate = Resource::class;
			case 'file':
				$classToCreate = \App\File::class;
			case 'audio':
				$classToCreate = \App\AudioFile::class;
			case 'video':
				$classToCreate = \App\VideoFile::class;
			case 'image':
				$classToCreate = \App\ImageFile::class;
			case 'doc':
				$classToCreate = \App\DocumentFile::class;
			case 'book':
				$classToCreate = \App\Book::class;
			case 'text':
				$classToCreate = \App\Text::class;
		}


		$obj = $classToCreate::create($data);

		return redirect('/resource/' . $obj->id);

	}

	/**
	 * Display the specified resource.
	 *
	 * @param  Resource $resource
	 * @return \Illuminate\Http\Response
	 */
	public function show(Resource $resource) {
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  Resource $resource
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Resource $resource) {
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Resource            $resource
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Resource $resource) {
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  Resource $resource
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(Resource $resource) {
		//
	}
}
