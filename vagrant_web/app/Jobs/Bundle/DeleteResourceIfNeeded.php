<?php

namespace App\Jobs\Bundle;

use App\Events\ResourceWasDeleted;
use App\Models\Bundle;
use App\Models\File;
use App\Models\ForeignResourceId;
use App\Models\Resource;
use App\Services\Bundles\BundlesService;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\ResourceHandlingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteResourceIfNeeded implements ShouldQueue, VersionInterface {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


	/** @var  Bundle $bundle */
	protected $bundle;
	protected $foreignResourceId;
	protected $version;

	protected $localResourceId;

	/**
	 * Create a new job instance.
	 *
	 * @param $resourceToCheck Resource
	 *
	 */
	public function __construct(Bundle $bundle, ForeignResourceId $foreignResourceId, $version) {
		$this->bundle            = $bundle;
		$this->foreignResourceId = $foreignResourceId;
		$this->version           = $version;
	}

	/**
	 * Check if this resource does not exist anymore and needs to be deleted
	 *
	 * @param BundlesService          $bundlesService
	 * @param ResourceHandlingService $resourceHandlingService
	 * @throws \Exception
	 */
	public function handle(BundlesService $bundlesService, FileHandlingService $fileHandlingService) {

		$uuid = $this->foreignResourceId->foreign_id;

		if (!$bundlesService->hasFile($this->bundle, $uuid)) {

			/** @var File $resource */
			$resource = $this->foreignResourceId->resource;

			if ($resource && $resource->materials->count() == 0) {

				$resource->deleteLocalFile();
				$resource->delete();
				event(new ResourceWasDeleted($resource));

			}

			$this->foreignResourceId->delete();

		} else {
			// Resource still exists => Nothing to do
		}


	}

	public function getVersion() {
		return $this->version;
	}
}
