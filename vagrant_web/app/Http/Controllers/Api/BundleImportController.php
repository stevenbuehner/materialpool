<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Jobs\Bundle\DeleteMaterialIfNeeded;
use App\Jobs\Bundle\DeleteResourceIfNeeded;
use App\Jobs\Bundle\FinishImportAfterUpdate;
use App\Jobs\Bundle\InsertOrUpdateMaterial;
use App\Jobs\Bundle\InsertOrUpdateResource;
use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Services\Bundles\BundleQueueService;
use App\Services\Bundles\BundlesService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use League\Flysystem\FileNotFoundException;

class BundleImportController extends BaseController {

	protected $bundlesService;
	protected $bundleQueueService;

	public function __construct(BundlesService $bundlesService, BundleQueueService $bundleQueueService) {
		$this->bundlesService     = $bundlesService;
		$this->bundleQueueService = $bundleQueueService;
	}

	public function index() {

		$bundles = $this->bundlesService->updateInstalledBundleInfos();

		$localInfos = collect();

		/** @var Bundle $bundle */
		foreach ($bundles as $bundle) {
			try {

				$info = $this->bundlesService->getLocalBundleData($bundle);

				if ($info['version'] !== $bundle->installed_version && $bundle->update_available !== TRUE) {
					$bundle->update_available = TRUE;
					$bundle->save();
				}


				if ($info !== FALSE) {
					$localInfos->push($info);
				}

			} catch (FileNotFoundException $e) {

			}
		}

		return ['bundles' => $bundles,
				'infos'   => $localInfos];

	}

	public function initUpdate(Bundle $bundle) {

		// Init output
		$updateInProgress    = FALSE;
		$countDeletedJobs    = 0;
		$deleteJobs          = 0;
		$updateJobs          = 0;
		$alreadyExistingJobs = 0;
		// $updateAvailable  = FALSE;

		try {
			$bundleInfo = $this->bundlesService->getLocalBundleData($bundle);
		} catch (FileNotFoundException $e) {
			return response()->setStatusCode(505, $e->getMessage());
		}

		// Check if there is an update available
		$updateAvailable = ($bundleInfo['version'] !== $bundle->installed_version);


		if ($updateAvailable === TRUE) {

			// 1) Check if update is already in progress => continue
			$queueName           = $this->bundleQueueService->getQueueName($bundle);
			$alreadyExistingJobs = $this->bundleQueueService->countJobsInQueue($queueName);
			if ($alreadyExistingJobs > 0) {
				$jobVersion = $this->bundleQueueService->getFirstJobVersion($queueName);

				// Todo: Also check if the jobs are complete (by searching for the last job, that should be an instance of FinishImportAfterUpdate

				// If Versions are the same
				if ($bundleInfo['version'] == $jobVersion) {
					$updateInProgress = TRUE;
				} else {
					$updateInProgress = FALSE;

					// 2) Delete all old queue entires of this bundle
					$countDeletedJobs = $this->bundleQueueService->deleteOldBundleJobs($bundle);
				}

			} else {
				$updateInProgress = FALSE;
			}

			if ($updateInProgress === FALSE) {

				// 3) Insert all needed Jobs into queue
				$deleteJobs = $this->createDeleteJobs($bundle, $bundleInfo);
				$updateJobs = $this->createInsertOrUpdateJobs($bundle, $bundleInfo);
				$this->createFinishUpdateJob($bundle, $bundleInfo);
			}

		}


		// 4) Redirect to Processing the queue
		return [
			// 'bundle'      => $bundle,
			'updateAvailable' => $updateAvailable,
			'continueUpdate'  => $updateInProgress,
			'deletedJobs'     => $countDeletedJobs,
			'deleteJobs'      => $deleteJobs,
			'updateJobs'      => $updateJobs,
			'openJobs'        => $alreadyExistingJobs + $deleteJobs + $updateJobs
			// 'info'        => $info
		];

	}

	protected function createDeleteJobs($bundle, &$bundleInfo) {

		$queueName = $this->bundleQueueService->getQueueName($bundle);
		$count     = 0;
		$version   = $bundleInfo['version'];

		ForeignMaterialId::where('bundle_id', $bundle->id)
						 ->chunk(100, function (Collection $fmids) use ($bundle, $queueName, &$count, $version) {

							 foreach ($fmids as $fmid) {
								 $this->dispatch((new DeleteMaterialIfNeeded($bundle, $fmid,
																			 $version))->onQueue($queueName));
							 }

							 $count += $fmids->count();

						 });

		ForeignResourceId::where('bundle_id', $bundle->id)
						 ->chunk(100, function (Collection $frids) use ($bundle, $queueName, &$count, $version) {

							 foreach ($frids as $frid) {
								 $this->dispatch((new DeleteResourceIfNeeded($bundle, $frid,
																			 $version))->onQueue($queueName));
							 }

							 $count += $frids->count();

						 });


		return $count;

	}

	protected function createInsertOrUpdateJobs($bundle, &$bundleInfo) {
		$page      = 1;
		$perPage   = 100;
		$queueName = $this->bundleQueueService->getQueueName($bundle);
		$version   = $bundleInfo['version'];

		$count = 0;

		while (($files = collect($this->bundlesService->getBundleFiles($bundleInfo, $page++,
																	   $perPage)))->isNotEmpty()) {
			$count += $files->count();

			$files->each(function ($file) use ($bundle, $queueName, $version) {
				$this->dispatch((new InsertOrUpdateResource($bundle, $file, $version))->onQueue($queueName));
			});

		}

		$page    = 1;
		$perPage = 100;
		while (($files = collect($this->bundlesService->getBundleMaterials($bundleInfo, $page++,
																		   $perPage)))->isNotEmpty()) {
			$count += $files->count();

			$files->each(function ($material) use ($bundle, $queueName, $version) {
				$this->dispatch((new InsertOrUpdateMaterial($bundle, $material, $version))->onQueue($queueName));
			});

		}

		return $count;
	}

	protected function createFinishUpdateJob($bundle, $bundleInfo) {

		$queueName = $this->bundleQueueService->getQueueName($bundle);
		$this->dispatch((new FinishImportAfterUpdate($bundle, $bundleInfo['version']))->onQueue($queueName));

	}

	public function runJobs(Bundle $bundle, Request $request) {

		$start          = microtime(TRUE);
		$connectionName = config('queue.default');
		$queueName      = $this->bundleQueueService->getQueueName($bundle);
		$options        = new WorkerOptions(2, 128, 30, 0, 15);

		/** @var Worker $worker */
		$worker       = resolve('queue.worker');
		$finishedJobs = 0;
		$openJobs     = $this->bundleQueueService->countJobsInBundleQueue($bundle);

		while (microtime(TRUE) - $start <= 5 && $openJobs > 0) {
			$worker->runNextJob($connectionName, $queueName, $options);
			$finishedJobs++;
			$openJobs--;
		}

		// Aktualisieren, falls in der Zwischenzeit wieder neue Jobs angelegt wurden
		if ($openJobs == 0) {
			$openJobs = $this->bundleQueueService->countJobsInBundleQueue($bundle);
		}

		$result = [
			'done' => $finishedJobs,
			'open' => $openJobs
		];

		if ($openJobs === 0) {
			$result['bundle'] = $bundle->fresh();
		}

		return $result;

	}

}
