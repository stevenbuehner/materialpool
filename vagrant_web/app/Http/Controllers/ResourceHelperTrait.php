<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidResourceTypeException;
use App\Jobs\UpdateResourceHashes;
use App\Models\Material;
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

	protected function handleMultiResourceFileData(Request $request) {

		/** @var UploadedFile $uploadedFiles */
		$uploadedFiles = $request->file('file');

		if (!is_array($uploadedFiles)) {
			$uploadedFiles = [$uploadedFiles];
		}

		$resources = [];

		foreach ($uploadedFiles as $key => $file) {
			$resources[$key] = $this->handleResourceFileUpload($file);
			$resources[$key] = $this->handleGerneralResourceAttributes($resources[$key], $request);
		}

		return $resources;
	}

	protected function handleResourceFileUpload(UploadedFile $file, Resource $resource = NULL) {

		/** @var ResourceRecognitionService $recognitionService */
		/** @var UploadedFile $file */
		$recognitionService = resolve('app.resource.type.recognition');

		$resourceClass = $recognitionService->guessResourceFile($file);

		if ($resource) {
			if ($resource instanceof $resourceClass) {
				// continue
			} else {
				throw new InvalidResourceTypeException();
			}
		} else {
			$resource = new $resourceClass();
		}

		$this->handleCreatedByRessourceAttributes($resource);

		if ($resource instanceof \App\Models\File) {
			$disk = Storage::disk(config('app.disks.resources'));
			// $tmpPath                     = $uploadedFile->getPath() . DIRECTORY_SEPARATOR . $uploadedFile->getFilename();
			// $sha1                        = sha1_file($tmpPath);
			// $resource->content_hash      = $sha1;
			$resource->original_filename = $file->getClientOriginalName();
			$resource->save();

			$newTargetFolder      = DIRECTORY_SEPARATOR . intval($resource->id / 10000);
			$newTargetFolder      .= DIRECTORY_SEPARATOR . intval($resource->id / 100);
			$relativeFilePath     = $disk->putFile($newTargetFolder, $file);
			$resource->local_path = config('app.disks.resources') . '::' . $relativeFilePath;

		} else if ($resource instanceof Text) {
			$resource->content = \File::get($file->getRealPath());
		}

		$resource->save();

		UpdateResourceHashes::dispatch($resource);

		// Hashes have been updated ...
		$resource = $resource->fresh();

		return $resource;

	}

	protected function handleCreatedByRessourceAttributes(Resource $resource) {

		if ($resource->created_by === NULL) {
			$resource->created_by = Auth::id();
		}

		return $resource;
	}

	protected function handleGerneralResourceAttributes(Resource $resource, Request $request) {

		$resource->notes       = $request->get('notes', $resource->notes);
		$resource->is_public   = $request->get('is_public', $resource->is_public);
		$resource->remote_path = $request->get('remote_path', $resource->remote_path);

		$resource->save();

		return $resource;
	}

	/**
	 *
	 * @param $request
	 * @param $resource - optional resource to save data to
	 * @return Resource
	 *
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function handleSingleResourceFileData(Request $request, Resource $resource = NULL) {

		$uploadedFile = $request->file('file');
		$resource     = $this->handleResourceFileUpload($uploadedFile, $resource);
		$resource     = $this->handleGerneralResourceAttributes($resource, $request);

		return $resource;
	}

	/**
	 * @param Resource|Resource[] $resources
	 * @param string              $metaData
	 * @return Material
	 */
	protected function createMaterialFromResources($resources, $metaData = '') {

		if (!is_array($resources)) {
			$resources = [$resources];
		}

		$tagExtractionProperties               = [];
		$tagExtractionProperties['metatext'][] = $metaData;

		/** @var MaterialExtractionService $materialService */
		$materialService = resolve(MaterialExtractionService::class);
		$material        = $materialService->createGuessedMaterialFromResource($resources, $tagExtractionProperties);

		return $material;
	}

	protected function handleContentResourceUpload(Request $request, Resource $resource = NULL) {
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

		$this->handleCreatedByRessourceAttributes($resource);
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
