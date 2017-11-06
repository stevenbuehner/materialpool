<?php

namespace App\Jobs;

use App\Models\Bibleverse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CheckLonelyBibleverse implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable;

	protected $bibleverseToCheck;

	/**
	 * Create a new job instance.
	 *
	 * @param $bibleverseToCheck Resource
	 *
	 */
	public function __construct(Bibleverse $bibleverseToCheck) {
		$this->bibleverseToCheck = $bibleverseToCheck;
	}

	/**
	 * Execute the job.
	 *
	 */
	public function handle() {

		// Test for material-keyword-relationship
		$BibleverseHasMaterial = $this->bibleverseToCheck::has('materials')
														 ->where('id', '=', $this->bibleverseToCheck->id)
														 ->take(1)
														 ->get()
														 ->count();

		if ($BibleverseHasMaterial === 0) {
			Log::info("Delete bibleverse from={$this->bibleverseToCheck->from}; to={$this->bibleverseToCheck->to} because it was lonely");
			$this->bibleverseToCheck->delete();
		}

	}
}
