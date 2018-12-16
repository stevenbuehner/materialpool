<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\BibleContent;


class BibleContentController extends BaseController {

	public function __construct() {
	}

	public function getBibleverse(int $from, int $to, $bibleId = NULL) {

		$query = BibleContent::whereBetween('verse', [$from, $to])
							 ->orderBy('verse', 'asc');

		if (!is_null($bibleId)) {
			$query = $query->where('bible_id', '=', $bibleId);
		}

		return $query->get();


	}

}
