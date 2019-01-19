<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Bible;
use App\Models\BibleContent;
use App\Models\Bibleverse;
use Illuminate\Http\Request;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;


class BibleContentController extends BaseController {

	protected $bibleVerseService;

	public function __construct(BibleVerseService $bibleVerseService) {
		$this->bibleVerseService = $bibleVerseService;
	}

	public function getBibleverse(int $from, int $to, $bibleId = NULL) {

		$query = BibleContent::whereBetween('verse', [$from, $to])
							 ->orderBy('verse', 'asc');

		if (!is_null($bibleId)) {
			$query = $query->where('bible_id', '=', $bibleId);
		}

		return $query->get();

	}

	public function searchAndGet(Request $request, $bibleUid = NULL) {

		$search = $request->get('search', '');
		$bvs    = $this->bibleVerseService->stringToBibleVerse($search);

		// Max 20 Querried Bibleverses
		if (count($bvs) > 20) {
			$bvs = array_splice($bvs, 0, 20);
		}

		$searchBvs = $this->bibleVerseService->mergeBibleverses($bvs);

		if (count($searchBvs) === 0) {
			return [];
		}

		$query = BibleContent::query()->with('bible');

		foreach ($searchBvs as $bv) {
			$b2 = Bibleverse::makeFromBibleverseInterface($bv);
			$query->whereBetween('verse', [$b2->from, $b2->to]);
		}

		if ($bibleUid !== NULL) {
			$bible = Bible::where('uuid', '=', $bibleUid)->firstOrFail();
		} else {
			// Das sollte eine Bibel sein, die AT + NT hat!
			$bible = Bible::firstOrFail();
		}

		$query->where('bible_id', '=', $bible->id);


		return $query->get();
	}

}
