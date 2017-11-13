<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Models\Material;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckLonelyKeyword implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $keywordToCheck;

	/**
	 * Create a new job instance.
	 *
	 * @param $keywordToCheck Resource
	 *
	 */
	public function __construct(Keyword $keywordToCheck) {
		$this->keywordToCheck = $keywordToCheck;
	}

	/**
	 * Execute the job.
	 *
	 */
	public function handle() {

		// Test for material-keyword-relationship
		$keywordHasMaterial = Keyword::has('materials')
									 ->where('id', '=', $this->keywordToCheck->id)
									 ->take(1)
									 ->get()
									 ->count();

		if ($keywordHasMaterial !== 0) {
			return;
		}

		// Test for material-author-relationship
		$keywordInAuthor = Material::has('author')
								   ->where('author_id', '=', $this->keywordToCheck->id)
								   ->take(1)
								   ->get();

		if ($keywordInAuthor === FALSE) {
			Log::info('Deleting Keyword "' . $this->keywordToCheck->title . '" because it was lonely');
			$this->keywordToCheck->delete();
		}
	}
}
