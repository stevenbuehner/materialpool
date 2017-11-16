<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidResourceTypeException;
use App\Jobs\UpdateResourceHashes;
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
	 *
	 * @param $request
	 * @param $resource - optional resource to save data to
	 * @return Resource
	 *
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function handleResourceUpload(Request $request, Resource $resource = NULL) {

		/** @var ResourceRecognitionService $recognitionService */
		/** @var UploadedFile $uploadedFile */
		$recognitionService = resolve('app.resource.type.recognition');
		$uploadedFile       = $request->file('file');
		$resourceClass      = $recognitionService->guessResourceFile($uploadedFile);

		if ($resource) {
			if ($resource instanceof $resourceClass) {
				// continue
			} else {
				throw new InvalidResourceTypeException();
			}
		} else {
			$resource = new $resourceClass();
		}

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

		// Hashes have been updated ...
		$resource = $resource->fresh();

		return $resource;
	}

	protected function handleGerneralResourceAttributes(Resource $resource, Request $request) {

		if ($resource->created_by === NULL) {
			$resource->created_by = Auth::id();
		}

		$resource->notes       = $request->get('notes', $resource->notes);
		$resource->is_public   = $request->get('is_public', $resource->is_public);
		$resource->remote_path = $request->get('remote_path', $resource->remote_path);

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

	protected function handleResourceContent(Request $request, Resource $resource = NULL) {
		/** @var ResourceRecognitionService $recognitionService */
		$recognitionService = resolve('app.resource.type.recognition');
		$content            = $request->get('content',
											$resource instanceof TextContentInterface ? $resource->getContent() : '');
		$resourceClass      = $recognitionService->guessResourceContent($content);

		if ($resource) {
			if ($resource instanceof $resourceClass) {
				// continue
			} else {
				throw new InvalidResourceTypeException();
			}
		} else {
			$resource = new $resourceClass();
		}

		$this->handleGerneralResourceAttributes($resource, $request);

		if ($resource instanceof TextContentInterface) {
			$resource->setContent($content);
		}

		$resource->save();

		UpdateResourceHashes::dispatch($resource);

		// Hash will be updated ...
		$resource = $resource->fresh();

		return $resource;
	}

	/**
	 * @param Request $request
	 * @param string  $resourceClass
	 * @param bool    $partialUpdateAllowed only use rules for the parameters in $request (don't require any other parameters)
	 * @throws \Illuminate\Validation\ValidationException
	 */
	protected function validateResourceRequest(Request $request, $resourceClass, $partialUpdateAllowed = FALSE) {

		$rules = $resourceClass::getValidationRules();

		if ($partialUpdateAllowed === TRUE) {
			$rules = array_only($rules, $request->keys());
		}

		$validator = \Validator::make($request->all(), $rules);
		$validator->validate();
	}

}
