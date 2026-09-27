<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Services\KeywordHandling\KeywordHandlingService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckLonelyKeyword implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/** @var Keyword $keywordToCheck */
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
	 * @param KeywordHandlingService $keywordHandlingService
	 * @throws Exception
	 */
	public function handle(KeywordHandlingService $keywordHandlingService) {
		$parent = $this->keywordToCheck->parent;

		if ($this->deleteKeywordIfLonely($keywordHandlingService, $this->keywordToCheck) === TRUE) {

			// Check parent for lonelines as well
			if ($parent !== NULL) {
				CheckLonelyKeyword::dispatch($parent);
			}

		}
	}

	protected function deleteKeywordIfLonely(KeywordHandlingService $keywordHandlingService, Keyword $keyword) {

		// Test for material-keyword-relationship (other materials use this keyword)
		if ($keywordHandlingService->isKeywordUsedByMaterials($keyword)) {
			Log::info('NOT deleting Keyword "' . $keyword->title . '" because it has assigned materials');
			return FALSE;
		}

		// Test for material-author-relationship (Keyword used as author)
		if ($keywordHandlingService->isKeywordUsedAsMaterialAuthor($keyword)) {
			Log::info('NOT deleting Keyword "' . $keyword->title . '" because it is used as material-author');
			return FALSE;
		}

		// Es ist gar nicht nötig alle Unterkinder zu überprüfen. Denn die dürften theoretisch ja gar nicht "lonely" sein, sonst wären sie ebenfalls automatisch gelöscht worden
		// Check each of the children for loneliness and delete them if possible
		// If one child fails to be deleted, don't delete this one either
		/*
		 $children = $keyword->children;
		 foreach ($children as $child) {
			$deleted = $this->deleteKeywordIfLonely($keywordHandlingService, $child);

			if ($deleted === FALSE) {
				Log::info('NOT deleting Keyword "' . $keyword->title . '" because it has required children');
				break;
			}
		}

		$keyword->load('children');
		*/

		if ($keyword->children->count() === 0) {

			Log::info('Deleting Keyword "' . $keyword->title . '" because it was lonely');
			$keywordHandlingService->deleteKeyword($keyword, $withChildren = FALSE);

			return TRUE;
		} else {
			Log::info('NOT deleting Keyword "' . $keyword->title . '" because it has children');
			return FALSE;
		}

	}

}
