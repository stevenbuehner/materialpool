<?php

namespace App\Http\Controllers;

use App\Events\ResourceWasChanged;
use App\Events\ResourceWasCreated;
use App\Exceptions\InvalidResourceTypeException;
use App\Models\File;
use App\Models\Material;
use App\Models\Resource;
use App\Models\Text;
use App\Services\ResourceRecognition\ResourceRecognitionService;
use App\Services\TagExtraction\MaterialExtractionService;
use App\Services\TagExtraction\ResourceHandles\TextContentInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
			try {
				$resources[$key] = $this->handleResourceFileUpload($file);
				$resources[$key] = $this->handleGerneralResourceAttributes($resources[$key], $request);

				event(new ResourceWasCreated($resources[$key]));

			} catch (\Exception $e) {
				//Todo: Cleanup again
				Log::error('Error during File-Upload', [
					'message' => $e->getMessage(),
					'trace'   => $e->getTrace()
				]);

				if ($resources[$key] instanceof File) {

				}


			}

		}

		return $resources;
	}

	protected function handleResourceFileUpload(UploadedFile $file, Resource $resource = NULL) {

		/** @var ResourceRecognitionService $recognitionService */
		/** @var UploadedFile $file */
		$recognitionService = resolve(ResourceRecognitionService::class);

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

			$newTargetFolder = DIRECTORY_SEPARATOR . intval($resource->id / 10000);
			$newTargetFolder .= DIRECTORY_SEPARATOR . intval($resource->id / 100);

			$fileHash = str_replace('.' . $file->extension(), '', $file->hashName());
			$fileName = $fileHash . '.' . $file->getClientOriginalExtension();

			$relativeFilePath = $disk->putFileAs($newTargetFolder, $file, $fileName);

			$resource->setLocalStorageAndPath(config('app.disks.resources'), $relativeFilePath);

		} else if ($resource instanceof Text) {
			$resource->content           = \File::get($file->getRealPath());
			// $resource->original_filename = $file->getClientOriginalName();
		}

		if ($resource->isDirty()) {
			$resource->save();
		}

		Log::info("Resource ({$resource->id}, {$resource->original_filename}) was created and uploaded to: {$resource->local_path}");

		// Hashes have been updated ...
		// $resource = $resource->fresh();

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

		$postEvent = $resource === NULL ? ResourceWasCreated::class : ResourceWasChanged::class;

		try {
			$uploadedFile = $request->file('file');
			$resource     = $this->handleResourceFileUpload($uploadedFile, $resource);
			$resource     = $this->handleGerneralResourceAttributes($resource, $request);

			event(new $postEvent($resource));

		} catch (\Exception $e) {
			//Todo: Cleanup again
			Log::error('Error during File-Upload', [
				'message' => $e->getMessage(),
				'trace'   => $e->getTraceAsString()
			]);

		}


		return $resource;
	}

	/**
	 * @param Resource|Resource[] $resources
	 * @param string $metaData
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
		$recognitionService = resolve(ResourceRecognitionService::class);
		$content            = $request->get('content',
			$resource instanceof TextContentInterface ? $resource->getContent() : '');
		$resourceClass      = $recognitionService->guessResourceContent($content);
		$postEvent          = $resource === NULL ? ResourceWasCreated::class : ResourceWasChanged::class;


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

		try {

			event(new $postEvent($resource));

		} catch (\Exception $e) {
			//Todo: Cleanup again
			Log::error('Error during Post-CreationJobs', [
				'message' => $e->getMessage(),
				'trace'   => $e->getTrace()
			]);

		}

		// Hash will be updated ...
		// $resource = $resource->fresh();

		return $resource;
	}


	/**
	 * @param Request $request
	 * @param string $resourceClass
	 * @param bool $partialUpdateAllowed only use rules for the parameters in $request (don't require any other parameters)
	 * @throws \Illuminate\Validation\ValidationException
	 */
	protected function validateResourceRequest(Request $request, $resourceClass, $partialUpdateAllowed = FALSE) {

		$rules = $resourceClass::getValidationRules();

		if ($partialUpdateAllowed === TRUE) {
			// The Arr::only method returns only the specified key / value pairs from the given array:
			$rules = Arr::only($rules, $request->keys());
		}

		$validator = \Validator::make($request->all(), $rules);
		$validator->validate();
	}

}
