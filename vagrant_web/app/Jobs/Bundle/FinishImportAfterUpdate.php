<?php

namespace App\Jobs\Bundle;

use App\Models\Bundle;
use App\Services\Bundles\BundleQueueService;
use App\Services\Bundles\BundlesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FinishImportAfterUpdate implements ShouldQueue, VersionInterface {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


	/** @var  Bundle $bundle */
	protected $bundle;
	protected $version;

	/**
	 * InsertOrUpdateResource constructor.
	 *
	 * @param Bundle $bundle
	 * @param        $localFileInfo
	 */
	public function __construct(Bundle $bundle, $version) {
		$this->bundle  = $bundle;
		$this->version = $version;

	}

	public function handle(BundleQueueService $bundleQueueService, BundlesService $bundlesService) {

		$queueName = $this->queue;
		$countJobs = $bundleQueueService->countJobsInQueue($this->queue);

		if ($countJobs > 1) {#
			// Push Job to the end and increase attempts +1
			$this->release(60);

			// $this->delay(now()->addSeconds(10));


			return;
		} else {

			$bundleInfo = $bundlesService->getLocalBundleData($this->bundle);


			$this->bundle->name              = $bundleInfo["name"];
			$this->bundle->description       = $bundleInfo['description'];
			$this->bundle->author            = $bundleInfo["author"];
			$this->bundle->installed_version = $this->version;
			$this->bundle->update_available  = FALSE;
			$this->bundle->is_installed      = TRUE;
			$this->bundle->touch();


			//		$this->bundle->save(); // done with touch()

		}

	}

	public function getVersion() {
		return $this->version;
	}
}
