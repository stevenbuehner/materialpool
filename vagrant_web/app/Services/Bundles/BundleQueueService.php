<?php

namespace App\Services\Bundles;

use App\Jobs\Bundle\FinishImportAfterUpdate;
use App\Jobs\Bundle\VersionInterface;
use App\Models\Bundle;
use Illuminate\Queue\Worker;
use Illuminate\Support\Facades\DB;

class BundleQueueService {


	public function __construct() {
	}

	public function deleteOldBundleJobs(Bundle $bundle) {

		$countDeletedJobs = DB::delete('DELETE FROM jobs WHERE queue=:QUEUE',
									   ['QUEUE' => $this->getQueueName($bundle)]);

		return $countDeletedJobs;

	}

	public function getQueueName(Bundle $bundle) {
		return 'bundle_' . $bundle->id . '_queue';
	}

	public function countJobsInBundleQueue(Bundle $bundle) {
		return $this->countJobsInQueue($this->getQueueName($bundle));
	}

	public function countJobsInQueue($queueName) {

		/** @var Worker $worker */
		$connectionName = 'database';
		$worker         = resolve('queue.worker');
		$queue          = $worker->getManager()->connection($connectionName);

		return $queue->size($queueName);


		$data = DB::select('SELECT count(*) as Anzahl FROM jobs WHERE queue=:QUEUE',
						   ['QUEUE' => $queueName]);

		return (int) $data[0]->Anzahl;
	}

	/**
	 *
	 * Gets Version or NULL on error
	 *
	 * @param $queueName
	 * @return null|string
	 */
	public function getFirstJobVersion($queueName) {

		$job = DB::select('SELECT * FROM jobs WHERE queue=:QUEUE ORDER BY id asc LIMIT 1;',
						  ['QUEUE' => $queueName]);

		if (count($job) > 0) {
			try {

				$job     = array_pop($job);
				$payload = json_decode($job->payload);
				$data    = unserialize($payload->data->command);

				if ($data instanceof VersionInterface) {
					return $data->getVersion();
				}

			} catch (\Exception $e) {

			}
		}

		return NULL;

	}

	public function hasFinishImportAfterUpdateJob($queueName) {

		$job = DB::select('SELECT * FROM jobs WHERE queue=? AND payload LIKE "%?%" ORDER BY id asc LIMIT 1;',
						  [$queueName,
						   FinishImportAfterUpdate::class]);

		return count($job) > 0;

	}


}