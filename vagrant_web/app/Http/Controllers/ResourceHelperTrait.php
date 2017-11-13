<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateResourceHashes;
use App\Models\File;
use App\Models\Resource;
use App\Models\Text;
use App\Services\ResourceRecognition\ResourceRecognitionService;
use App\Services\TagExtraction\MaterialExtractionService;
use App\Services\TagExtraction\ResourceHandles\TextContentInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

trait ResourceHelperTrait {

	protected $bibleVerseService;


	protected function checkResourceRequirements(Request $request) {
		if ($request->hasFile()) {

		}

		return TRUE;

	}

	/**
	 * @return Resource
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function handleResourceUpload(Request $request) {

		/** @var ResourceRecognitionService $recognitionService */
		/** @var UploadedFile $uploadedFile */
		$recognitionService = resolve('app.resource.type.recognition');
		$uploadedFile       = $request->file('file');
		$resourceClass      = $recognitionService->guessResourceFile($uploadedFile);
		$resource           = new $resourceClass();

		$this->handleGerneralResourceAttributes($resource, $request);

		if ($resource instanceof \App\Models\File) {
			$disk = Storage::disk(config('app.disks.resources'));
			// $tmpPath                     = $uploadedFile->getPath() . DIRECTORY_SEPARATOR . $uploadedFile->getFilename();
			// $sha1                        = sha1_file($tmpPath);
			// $resource->content_hash      = $sha1;
			$resource->original_filename = $uploadedFile->getClientOriginalName();
			$resource->save();

			$newTargetFolder      = DIRECTORY_SEPARATOR . intval($resource->id / 10000);
			$newTargetFolder      .= DIRECTORY_SEPARATOR . intval($resource->id / 100);
			$relativeFilePath     = $disk->putFile($newTargetFolder, $uploadedFile);
			$resource->local_path = config('app.disks.resources') . '::' . $relativeFilePath;


		} else if ($resource instanceof Text) {
			$resource->content = \File::get($uploadedFile->getRealPath());
		}

		$resource->save();

		UpdateResourceHashes::dispatch($resource);

		return $resource;
	}

	protected function handleGerneralResourceAttributes(Resource $resource, Request $request) {
		$resource->created_by = Auth::id();
		$resource->notes      = $request->get('notes', '');
		$resource->is_public  = $request->get('is_public', $resource->is_public);

		return $resource;
	}

	/**
	 * @param        $resources
	 * @param string $metaData
	 * @return Material
	 */
	protected function createMaterialFromResources($resources, $metaData = '') {

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

	protected function handleResourceContent(Request $request) {
		/** @var ResourceRecognitionService $recognitionService */
		$recognitionService = resolve('app.resource.type.recognition');
		$content            = $request->get('content', '');
		$resourceClass      = $recognitionService->guessResourceContent($content);

		$resource = new $resourceClass();

		$this->handleGerneralResourceAttributes($resource, $request);

		if ($resource instanceof TextContentInterface) {
			$resource->setContent($content);
		}

		$resource->save();

		UpdateResourceHashes::dispatch($resource);

		return $resource;
	}

}
