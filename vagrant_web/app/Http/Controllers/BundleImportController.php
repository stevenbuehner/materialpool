<?php

namespace App\Http\Controllers;

use App\Jobs\Bundle\DeleteMaterialIfNeeded;
use App\Jobs\Bundle\DeleteResourceIfNeeded;
use App\Jobs\Bundle\InsertOrUpdateMaterial;
use App\Jobs\Bundle\InsertOrUpdateResource;
use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Services\Bundles\BundlesService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use League\Flysystem\FileNotFoundException;

class BundleImportController extends Controller {

	protected $bundlesService;

	public function __construct(BundlesService $bundlesService) {
		$this->middleware('auth');
		// Todo: Check authorization
		$this->bundlesService = $bundlesService;
	}

	public function index() {

		$this->bundlesService->updateInstalledBundleInfos();

		$bundles    = Bundle::all();
		$localInfos = collect();

		foreach ($bundles as $bundle) {
			try {

				$info = $this->bundlesService->getLocalBundleData($bundle);

				if ($info !== FALSE) {
					$localInfos->put($bundle->uuid, $info);
				}

			} catch (FileNotFoundException $e) {
			}
		}


		return view('bundles.index', ['bundles' => $bundles, 'infos' => $localInfos]);
	}

	public function initUpdate(Bundle $bundle) {

		$info = $this->bundlesService->getLocalBundleData($bundle);


		// 1) Delete all old queue entires of this bundle
		$countDeletedJobs = $this->deleteOldBundleJobs($bundle);

		// 2) Insert all Jobs into queue
		$deleteJobs = $this->createDeleteJobs($bundle);
		$updateJobs = $this->createInsertOrUpdateJobs($bundle);

		// 3) Redirect to Processing the queue
		return view('bundles.update-init', [
			'bundle'      => $bundle,
			'deletedJobs' => $countDeletedJobs,
			'deleteJobs'  => $deleteJobs,
			'updateJobs'  => $updateJobs,
			'info'        => $info
		]);

	}

	protected function deleteOldBundleJobs(Bundle $bundle) {

		$countDeletedJobs = DB::delete('DELETE FROM jobs WHERE queue=:QUEUE', ['QUEUE' => $this->getQueue($bundle)]);

		return $countDeletedJobs;


		$connectionName = config('queue.default');
		$queue          = $this->getQueue($bundle);

		/** @var Worker $worker */
		$worker = resolve('queue.worker');

		/** @var QueueManager $manager */
		$manager = $worker->getManager();

		/** @var DatabaseQueue $connection */
		$connection = $manager->connection($connectionName);


		$countDeletedJobs = 0;
		while ($job = $connection->pop($queue)) {
			$countDeletedJobs++;
			$job->delete();
		}

		return $countDeletedJobs;
	}

	protected function getQueue(Bundle $bundle) {
		return 'bundle_' . $bundle->id . '_queue';
	}

	protected function createDeleteJobs($bundle) {

		$queue = $this->getQueue($bundle);
		$count = 0;

		ForeignMaterialId::where('bundle_id', $bundle->id)
						 ->chunk(100, function (Collection $fmids) use ($bundle, $queue, &$count) {

							 foreach ($fmids as $fmid) {
								 $this->dispatch((new DeleteMaterialIfNeeded($bundle, $fmid))->onQueue($queue));
							 }

							 $count += $fmids->count();

						 });

		ForeignResourceId::where('bundle_id', $bundle->id)
						 ->chunk(100, function (Collection $frids) use ($bundle, $queue, &$count) {

							 foreach ($frids as $frid) {
								 $this->dispatch((new DeleteResourceIfNeeded($bundle, $frid))->onQueue($queue));
							 }

							 $count += $frids->count();

						 });


		return $count;

	}

	protected function createInsertOrUpdateJobs($bundle) {
		$page    = 1;
		$perPage = 100;
		$queue   = $this->getQueue($bundle);

		$count = 0;

		while (($files = collect($this->bundlesService->getBundleFiles($bundle, $page++, $perPage)))->isNotEmpty()) {
			$count += $files->count();

			$files->each(function ($file) use ($bundle, $queue) {
				$this->dispatch((new InsertOrUpdateResource($bundle, $file))->onQueue($queue));
			});

		}

		$page    = 1;
		$perPage = 100;
		while (($files = collect($this->bundlesService->getBundleMaterials($bundle, $page++,
																		   $perPage)))->isNotEmpty()) {
			$count += $files->count();

			$files->each(function ($material) use ($bundle, $queue) {
				$this->dispatch((new InsertOrUpdateMaterial($bundle, $material))->onQueue($queue));
			});

		}

		return $count;
	}

	public function runJobs(Bundle $bundle) {

		$start          = microtime(TRUE);
		$connectionName = config('queue.default');
		$queue          = $this->getQueue($bundle);
		$options        = new WorkerOptions(0, 128, 30, 0, 2);

		/** @var Worker $worker */
		$worker       = resolve('queue.worker');
		$finishedJobs = 0;
		$openJobs     = $this->countJobsInQueue($bundle);

		while (microtime(TRUE) - $start <= 1 && $openJobs > 0) {
			$worker->runNextJob($connectionName, $queue, $options);
			$finishedJobs++;
			$openJobs--;
		}

		// Aktualisieren, falls in der Zwischenzeit wieder neue Jobs angelegt wurden
		if ($openJobs == 0) {
			$openJobs = $this->countJobsInQueue($bundle);
		}

		if ($openJobs == 0) {
			$info                      = $this->bundlesService->getLocalBundleData($bundle);
			$bundle->installed_version = $info->version;
			$bundle->update_available  = FALSE;
			$bundle->is_installed      = TRUE;
			$bundle->setUpdatedAt($bundle->freshTimestamp());
			$bundle->save();
		}


		return response()->json([
									'done' => $finishedJobs,
									'open' => $openJobs
								]);

	}

	protected function countJobsInQueue(Bundle $bundle) {
		$data = DB::select('SELECT count(*) as Anzahl FROM jobs WHERE queue=:QUEUE',
						   ['QUEUE' => $this->getQueue($bundle)]);

		return (int) $data[0]->Anzahl;
	}


}
