<?php

namespace App\Jobs;

use App\Models\Material;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckLonelyMaterial implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $materialToCheck;

	/**
	 * Create a new job instance.
	 *
	 * @param $materialToCheck Material
	 *
	 */
	public function __construct(Material $materialToCheck) {
		$this->materialToCheck = $materialToCheck;
	}

	/**
	 * Execute the job.
	 *
	 */
	public function handle() {

		// Test for material-keyword-relationship
		$materialHasResource = $this->materialToCheck::has('resources')
													 ->where('id', '=', $this->materialToCheck->id)
													 ->take(1)
													 ->get()
													 ->count();

		if ($materialHasResource === 0) {
			Log::alert("This material seems to be lonely (has no resources attached). Material-id: {$this->materialToCheck->id}");

			// TODO: What to do with lonley material?
			// Do Nothing for now
		}

	}
}
