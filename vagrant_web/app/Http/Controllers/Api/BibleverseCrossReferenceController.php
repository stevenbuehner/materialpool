<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Bibleverse;


class BibleverseCrossReferenceController extends BaseController {


	public function __construct() {
	}

	public function getCrossReferences(int $from, int $to, int $perPage = 50) {

		$bv = new Bibleverse([
			'from' => $from,
			'to'   => $to
		]);

		return $bv->bibleverseCrossReferencesQuery()->paginate($perPage);

	}


}
