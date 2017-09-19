<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Resource as ResourceEntity;
use App\Models\Text;
use App\Services\ResourceRecognition\ResourceRecognitionService;
use App\Services\TagExtraction\MaterialExtractionService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

		if (!$request->hasFile('file')) {
			return redirect(route('pool.resource.create'))->withErrors(['Missing upload file']);
		}

		$uploadedFiles = $request->file('file');
		$metaData      = $request->get('meta', '');
		$resources     = [];

		foreach ($uploadedFiles as $file) {
			$resources[] = $this->handleResourceUpload($file);
		}

		$material = $this->createMaterialFromResources($resources, $metaData);

		return redirect(route('pool.material.show', $material->id));
	}

	/**
	 * @param UploadedFile $uploadedFile
	 * @return Resource
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function handleResourceUpload(UploadedFile $uploadedFile) {
		/** @var ResourceRecognitionService $recognitionService */
		$recognitionService   = resolve('app.resource.type.recognition');
		$resourceClass        = $recognitionService->guessResourceFile($uploadedFile);
		$resource             = new $resourceClass();
		$resource->created_by = Auth()->id();


		if ($resource instanceof \App\Models\File) {
			$disk                        = Storage::disk(config('app.disks.resources'));
			$tmpPath                     = $uploadedFile->getPath();
			$resource->content_hash      = sha1_file($tmpPath);
			$resource->original_filename = $uploadedFile->getClientOriginalName();
			$resource->save();

			$newTargetFolder      = DIRECTORY_SEPARATOR . intval($resource->id / 10000);
			$newTargetFolder      .= DIRECTORY_SEPARATOR . intval($resource->id / 100);
			$relativeFilePath     = $disk->putFile($newTargetFolder, $uploadedFile);
			$resource->local_path = config('app.disks.resources') . '::' . $relativeFilePath;

		} else if ($resource instanceof Text) {
			$resource->content = File::get($uploadedFile->getRealPath());
		}

		$resource->save();

		return $resource;
	}

	protected function createMaterialFromResources($resources, $metaData) {

		if (!is_array($resources)) {
			$resources = [$resources];
		}

		$tagExtractionProperties                 = [];
		$tagExtractionProperties['properties'][] = $metaData;

		/** @var MaterialExtractionService $materialService */
		$materialService = resolve(MaterialExtractionService::class);
		$material        = $materialService->createGuessedMaterialFromResource($resources, $tagExtractionProperties);

		return $material;
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
