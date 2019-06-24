<?php

namespace App\Jobs\Bundle;

use App\Events\ResourceWasChanged;
use App\Events\ResourceWasCreated;
use App\Http\Controllers\ResourceHelperTrait;
use App\Models\Bundle;
use App\Models\File;
use App\Models\ForeignResourceId;
use App\Models\Resource;
use App\Models\Text;
use App\Services\Bundles\BundlesService;
use App\Services\ResourceRecognition\ResourceRecognitionService;
use App\Services\TagExtraction\ResourceHandles\TextContentInterface;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InsertOrUpdateResource implements ShouldQueue, VersionInterface {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ResourceHelperTrait;


	/** @var  Bundle $bundle */
	protected $bundle;

	protected $localFileInfo;
	protected $version;

	/**
	 * InsertOrUpdateResource constructor.
	 *
	 * @param Bundle $bundle
	 * @param        $localFileInfo
	 */
	public function __construct(Bundle $bundle, $localFileInfo, $version) {
		$this->bundle        = $bundle;
		$this->localFileInfo = $localFileInfo;
		$this->version       = $version;

	}

	/**
	 * Execute the job.
	 *
	 * @param BundlesService $bundlesService
	 * @throws \Throwable
	 */
	public function handle(BundlesService $bundlesService) {


		/** @var ForeignResourceId $foreignRes */
		$foreignRes = ForeignResourceId::where(['foreign_id' => $this->getUUID()])
									   ->with(['resource'])->first();

		try {

			DB::beginTransaction();

			if ($foreignRes !== NULL && $foreignRes->resource !== NULL) {

				// Update
				/** @var File $resource */
				$resource = $foreignRes->resource;

				// Just compare modified-timestamps and filePath from the last import

				if ($foreignRes->{ForeignResourceId::UPDATED_AT} != new Carbon($this->localFileInfo->file_modified) ||
					($resource instanceof File && $resource->getLocalFilePath() != $this->getLocalFilePath())
				) {

					$this->updateResource($bundlesService, $resource);

					event(new ResourceWasChanged($resource));

					$foreignRes->setCreatedAt($this->localFileInfo->file_created);
					$foreignRes->setUpdatedAt($this->localFileInfo->file_modified);

				}

				if ($foreignRes->bundle_id !== $this->bundle->id) {
					$foreignRes->bundle_id = $this->bundle->id;
				}

				if ($foreignRes->isDirty()) {
					$foreignRes->saveOrFail();
				}


			} else {

				// Insert
				$resource = $this->createResource($bundlesService);

				event(new ResourceWasCreated($resource));

				$foreignResource = new ForeignResourceId(
					[
						'user_id'     => $resource->created_by,
						'foreign_id'  => $this->localFileInfo->uuid,
						'resource_id' => $resource->id,
						'bundle_id'   => $this->bundle->id
					]
				);
				$foreignResource->setUpdatedAt($this->localFileInfo->file_modified);
				$foreignResource->setCreatedAt($this->localFileInfo->file_created);
				$foreignResource->saveOrFail();

			}

			DB::commit();
		} catch (\Exception $e) {
			DB::rollBack();

			throw $e;
		}


	}


	protected function getUUID() {
		return $this->localFileInfo->uuid;
	}

	protected function getLocalFilePath() {
		return $this->bundle->container_root . '/' . BundlesService::BUNDLE_FILES_DIR . '/' . $this->localFileInfo->file_path;
	}

	protected function updateResource(BundlesService $bundlesService, Resource $resource) {

		if ($resource->notes !== $this->localFileInfo->notes) {
			$resource->notes = $this->localFileInfo->notes;
		}

		if ($resource->is_public !== (bool) $this->localFileInfo->is_public) {
			$resource->is_public = (bool) $this->localFileInfo->is_public;
		}

		if ($resource->remote_path !== $this->localFileInfo->public_path) {
			$resource->remote_path = $this->localFileInfo->public_path;
		}

		if ($resource instanceof File) {
			$this->updateFileResource($bundlesService, $resource);
		} else if ($resource instanceof TextContentInterface) {
			$this->updateContentResource($bundlesService, $resource);
		}

		// Saving needed for PostQueueJobs
		if ($resource->isDirty()) {
			$resource->saveOrFail();
		}

		return $resource;

	}

	protected function updateFileResource(BundlesService $bundlesService, File $resource) {

		// TODO: Handle ResourceType has changed from text to file

		if ($resource->original_filename !== $this->localFileInfo->original_basename) {
			$resource->original_filename = $this->localFileInfo->original_basename;
		}

		if (!$resource->hasLocalFile() || $resource->getLocalFilePath() !== $this->getLocalFilePath()) {
			$resource->setLocalStorageAndPath($bundlesService->getBundleDiskName(), $this->getLocalFilePath());
		}

	}

	protected function updateContentResource(BundlesService $bundlesService, Text $resource) {

		// TODO: Handle ResourceType has changed from file to text

		$content = '';

		if ($this->getLocalFilePath()) {

			$storage = Storage::disk($bundlesService->getBundleDiskName());

			try {
				$content = $storage->get($this->getLocalFilePath());
			} catch (FileNotFoundException $e) {
				$content = '';
			}

		}

		$resource->setContent($content);

	}


	/**
	 * @param BundlesService $bundlesService
	 * @return File
	 */
	protected function createResource(BundlesService $bundlesService) {

		/** @var ResourceRecognitionService $recognitionService */
		$recognitionService = resolve('app.resource.type.recognition');

		$resourceClass = $recognitionService->guessResourceFileFromMimeType($this->localFileInfo->mime_type);

		/** @var File $resource */
		$resource             = new $resourceClass();
		$resource->created_by = 1; // admin

		$resource = $this->updateResource($bundlesService, $resource);

		// $resource->saveOrFail();

		return $resource;
	}

	public function getVersion() {
		return $this->version;
	}

}
