<?php

namespace App\Http\Controllers;

use App\Models\Resource as ResourceEntity;
use App\Models\Text;
use App\Services\ResourceRecognition\ResourceRecognitionService;
use App\Services\TagExtraction\MaterialExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
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
		$recognitionService   = resolve('app.resource.type.recognition');
		$resourceClass        = $recognitionService->guessResourceFile($request->file('file'));
		$resource             = new $resourceClass();
		$resource->created_by = Auth()->id();

		// Create guessed Material
		$tagExtractionProperties                 = [];
		$tagExtractionProperties['properties'][] = $request->get('meta', '');


		if ($resource instanceof \App\Models\File) {
			$disk                        = Storage::disk(config('app.disks.resources'));
			$tmpPath                     = $request->file('file')->getPath();
			$resource->content_hash      = sha1_file($tmpPath);
			$resource->original_filename = $request->file('file')->getClientOriginalName();
			$localFilePath               = Auth()->id() . DIRECTORY_SEPARATOR . $resource->type;
			$filename                    = $disk->putFile($localFilePath, $request->file('file'));
			$resource->local_path        = config('app.disks.resources') . '::' . $filename;

		} else if ($resource instanceof Text) {
			$resource->content = File::get($request->file('file')->getRealPath());

			// Add filename to guessing properties
			$tagExtractionProperties['properties'][] = basename($request->file('file')->getClientOriginalName(),
																'.' . $request->file('file')
																			  ->getClientOriginalExtension());

			// TODO: if first line has multiple significant properties, delete it from text
			// $firstLine = strtok($resource->content, "\n");

		}

		$resource->save();


		/** @var MaterialExtractionService $materialService */
		$materialService = resolve(MaterialExtractionService::class);
		$material        = $materialService->createGuessedMaterialFromResource($resource, $tagExtractionProperties);

		return redirect(route('pool.resource.edit', $resource->id));
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  ResourceEntity $resource
	 * @return \Illuminate\Http\Response
	 */
	public function show(ResourceEntity $resource) {
		$resource->load(['materials', 'materials.keywords', 'materials.bibleverses']);

		return view('resources.show')->with('resource', $resource);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  ResourceEntity $resource
	 * @return \Illuminate\Http\Response
	 */
	public function edit(ResourceEntity $resource) {
		$resource->load(['materials.keywords', 'materials.bibleverses']);

		return view('resources.edit', [
			'resource' => $resource
		]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  ResourceEntity           $resource
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, ResourceEntity $resource) {

		$resource->fill($request->all());
		$resource->save();

		return redirect(route('pool.resource.edit', $resource->id));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  ResourceEntity $resource
	 * @return \Illuminate\Http\Response
	 */
	public function destroy(ResourceEntity $resource) {
		//
	}

	public function download(ResourceEntity $resource) {

		if ($resource->is_public && !empty($resource->remote_path)) {
			return redirect()->to($resource->remote_path);
		} else if ($resource instanceof \App\Models\File) {
			$stream = $resource->getLocalFileStream();

			return Response::stream(function () use ($stream) {
				fpassthru($stream);
			}, 200, [
				'Content-Type'        => $resource->getLocalMimeType(),
				'Content-Length'      => $resource->getLocalSize(),
				'Content-disposition' => "attachment; filename=\"" . $resource->getOriginalFilenameAttribute() . "\""
			]);
		} else if ($resource instanceof Text) {
			return Response::make($resource->content, 200, [
				'Content-type'        => 'text/plain',
				'Content-Disposition' => "attachment; filename=\"resource id" . $resource->id . ".txt\"",
				'Content-Length'      => sizeof($resource->content)
			]);
		}
	}
}
