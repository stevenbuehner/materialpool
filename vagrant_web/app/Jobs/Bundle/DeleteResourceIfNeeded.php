<?php

namespace App\Jobs\Bundle;

use App\Models\Bundle;
use App\Models\File;
use App\Models\ForeignResourceId;
use App\Services\Bundles\BundlesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteResourceIfNeeded implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


	/** @var  Bundle $bundle */
	protected $bundle;
	protected $foreignResourceId;

	protected $localResourceId;

	/**
	 * Create a new job instance.
	 *
	 * @param $resourceToCheck Resource
	 *
	 */
	public function __construct(Bundle $bundle, ForeignResourceId $foreignResourceId) {
		$this->bundle            = $bundle;
		$this->foreignResourceId = $foreignResourceId;
	}

	/**
	 * Check if this resource does not exist anymore and needs to be deleted
	 *
	 */
	public function handle(BundlesService $bundlesService) {

		$uuid = $this->foreignResourceId->foreign_id;

		if (!$bundlesService->hasFile($this->bundle, $uuid)) {

			/** @var File $resource */
			$resource = $this->foreignResourceId->resource;

			if ($resource->materials()->count() == 0) {

				$resource->deleteLocalFile();
				$resource->delete();

			}

		} else {
			// Material still exists => Nothing to do
		}


	}
}
