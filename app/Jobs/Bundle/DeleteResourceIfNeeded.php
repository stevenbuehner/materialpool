<?php

namespace App\Jobs\Bundle;

use App\Models\Bundle;
use App\Models\File;
use App\Models\ForeignResourceId;
use App\Models\Resource;
use App\Services\Bundles\BundlesService;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\ResourceHandlingService;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class DeleteResourceIfNeeded implements ShouldQueue, VersionInterface {
	use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 120;


	/** @var  Bundle $bundle */
	protected $bundle;
	protected $foreignResourceId;
	protected $version;
	protected $uninstall;

	/**
	 * Create a new job instance.
	 *
	 * @param $resourceToCheck Resource
	 *
	 */
	public function __construct(Bundle $bundle, ForeignResourceId $foreignResourceId, $version, $uninstall = FALSE) {
		$this->bundle            = $bundle;
		$this->foreignResourceId = $foreignResourceId;
		$this->version           = $version;
		$this->uninstall         = $uninstall;
	}

	/**
	 * Check if this resource does not exist anymore in the external bundle and therefore needs to be deleted locally as well
	 *
	 * @param BundlesService $bundlesService
	 * @param ResourceHandlingService $resourceHandlingService
	 * @throws Exception
	 */
	public function handle(BundlesService $bundlesService, FileHandlingService $fileHandlingService) {
		if ($this->batch()?->cancelled()) {
			return;
		}

		$uuid = $this->foreignResourceId->foreign_id;

		if ($this->uninstall || !$bundlesService->hasFile($this->bundle, $uuid)) {

			/** @var File $resource */
			$resource = $this->foreignResourceId->resource;

			if ($resource && $resource->materials->count() == 0 && $resource->foreignIds->count() === 1) {
				$fileHandlingService->deleteResourceCompletely($resource, TRUE);
			} else {
				// Resource nicht löschen, weil andere Materialien noch mit dieser Ressource verknüpft sind
				$this->foreignResourceId->delete();
			}

		} else {
			// Resource still exists => Nothing to do
		}


	}

	public function middleware(): array {
		return [(new WithoutOverlapping('bundle:' . $this->bundle->id . ':delete-resource:' . $this->foreignResourceId->id))
			        ->shared()
			        ->releaseAfter(5)
			        ->expireAfter(180)];
	}

	public function getVersion() {
		return $this->version;
	}
}
