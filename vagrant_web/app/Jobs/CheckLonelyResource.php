<?php

namespace App\Jobs;

use App\Models\Resource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckLonelyResource implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $resourceToCheck;

	/**
	 * Create a new job instance.
	 *
	 * @param $resourceToCheck Resource
	 *
	 */
	public function __construct(Resource $resourceToCheck) {
		$this->resourceToCheck = $resourceToCheck;
	}

	/**
	 * Execute the job.
	 *
	 */
	public function handle() {

		// Test for material-resource-relationship
		$resourceHasMaterial = $this->resourceToCheck::has('materials')
			->where('id', '=', $this->resourceToCheck->id)
			->take(1)
			->get()
			->count();

		if ($resourceHasMaterial === 0) {
			Log::alert("This resource seems to be lonely (no material attached to it). Resource-id: {$this->resourceToCheck->id}");

			// TODO: What to do with lonley resources?
			// Do Nothing for now
		}

	}
}
