<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Text;
use App\Services\ResourceRecognition\ResourceRecognitionService;
use App\Services\TagExtraction\MaterialExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller {

	public function __construct() {
		$this->middleware(['auth']);
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {

		// $resources = DB::table('resources')->paginate(15);
		$resources = DB::table('resources')->paginate(20);

		return view('resources.index', compact('resources'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {
		return view('resources.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function storeFile(Request $request) {
		// Mass assignment easy


		if (!$request->hasFile('file')) {
			return redirect(route('pool.resource.create'))->withErrors(['Missing upload file']);
		}

		/** @var ResourceRecognitionService $recognitionService */
		$disk               = Storage::disk(config('app.disks.resources'));
		$tmpPath            = $request->file('file')->getPath();
		$sha1               = sha1_file($tmpPath);
		$recognitionService = resolve('app.resource.type.recognition');

		$resourceClass               = $recognitionService->guessResourceFileClass($request
																					   ->file('file')
																					   ->getMimeType());
		$resource                    = new $resourceClass();
		$resource->created_by        = Auth()->id();
		$resource->content_hash      = $sha1;
		$resource->original_filename = $request->file('file')->getClientOriginalName();


		$localFilePath        = Auth()->id() . DIRECTORY_SEPARATOR . $resource->type;
		$filename             = $disk->putFile($localFilePath, $request->file('file'));
		$resource->local_path = config('app.disks.resources') . '::' . $filename;
		$resource->save();


		// Create guessed Material
		$additionalInformation['properties'][] = $request->get('meta', '');

		/** @var MaterialExtractionService $materialService */
		$materialService = resolve(MaterialExtractionService::class);
		$material        = $materialService->createGuessedMaterialFromResource($resource, $additionalInformation);

		return redirect(route('pool.resource.edit', $resource->id));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function storeText(Request $request) {
		// Mass assignment easy

		$resource              = new Text();
		$resource->created_by  = Auth()->id();
		$additionalInformation = ['properties' => []];

		if ($request->hasFile('file')) {
			if ($request->file('file')->getMimeType() != 'text/plain') {
				return redirect(route('pool.resource.create'))->withErrors(['File is not a textfile.']);
			}

			$resource->content                     = File::get($request->file('file')->getRealPath());
			$additionalInformation['properties'][] = basename($request->file('file')->getClientOriginalName(),
															  '.' . $request->file('file')
																			->getClientOriginalExtension());
		} else if ($request->has('content') && strlen($request->get('content')) > 5) {

			$resource->content = $request->get('content');
		} else {
			return redirect(route('pool.resource.create'))->withErrors(['Missing a file or some text to create a ressource.']);
		}

		$resource->save();

		/** @var MaterialExtractionService $materialService */
		$materialService = resolve(MaterialExtractionService::class);
		$material        = $materialService->createGuessedMaterialFromResource($resource, $additionalInformation);

		return redirect(route('pool.resource.edit', $resource->id));
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  Resource $resource
	 * @return \Illuminate\Http\Response
	 */
	public function show(Resource $resource) {
		$resource->load(['materials', 'materials.keywords', 'materials.bibleverses']);

		return view('resources.show')->with('resource', $resource);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  Resource $resource
	 * @return \Illuminate\Http\Response
	 */
	public function edit(Resource $resource) {
		$resource->load(['materials.keywords', 'materials.bibleverses']);

		return view('resources.edit', [
			'resource' => $resource
		]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  Resource                 $resource
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, Resource $resource) {

		$resource->fill($request->all());
		$resource->save();

		return redirect(route('pool.resource.edit', $resource->id));
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
