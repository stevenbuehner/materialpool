<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Bible;
use App\Models\BibleContent;
use App\Models\Bibleverse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;


class BibleContentController extends BaseController {

	protected $bibleVerseService;

	public function __construct(BibleVerseService $bibleVerseService) {
		$this->bibleVerseService = $bibleVerseService;
	}

	public function getBibleverse(int $from, int $to, $bibleUid = NULL) {

		$query = BibleContent::whereBetween('verse', [$from, $to])
			->orderBy('verse', 'asc');

		$bible = $this->getBibleFirstOrFail($bibleUid);
		$query = $query->where('bible_id', '=', $bible->id);

		$bibleverses = $query->get();

		return [
			'bible'  => $bible->toArray(),
			'verses' => $bibleverses->map(function (BibleContent $content) use ($bible) {
				return [
					'text'      => $content->text,
					'verse'     => $content->verse,
					'bibleUuid' => $bible->uuid
				];
			})
		];

	}

	/**
	 * @param null|string $bibleUid
	 * @return Bible
	 * @throw Illuminate\Database\Eloquent\ModelNotFoundException;
	 */
	protected function getBibleFirstOrFail($bibleUid = NULL) {
		if ($bibleUid !== NULL) {
			$bible = Bible::where('uuid', '=', $bibleUid)->firstOrFail();
		} else {
			// Das sollte eine Bibel sein, die AT + NT hat!
			$bible = Bible::orderBy('usage_priority', 'DESC')->firstOrFail();
		}

		return $bible;
	}

	public function searchAndGet(Request $request, $bibleUid = NULL) {

		$search = $request->get('search') ?? '';
		$bvs    = $this->bibleVerseService->stringToBibleVerse($search);

		// Max 20 Querried Bibleverses
		if (count($bvs) > 20) {
			$bvs = array_splice($bvs, 0, 20);
		}

		$searchBvs = $this->bibleVerseService->mergeBibleverses($bvs);

		if (count($searchBvs) === 0) {
			return [];
		}

		$query = BibleContent::query(); // ->with('bible');

		foreach ($searchBvs as $bv) {
			$b2 = Bibleverse::makeFromBibleverseInterface($bv);
			$query->whereBetween('verse', [$b2->from, $b2->to]);
		}

		$bible   = NULL;
		$content = NULL;
		$error   = NULL;

		try {
			$bible = $this->getBibleFirstOrFail($bibleUid);

			$query->where('bible_id', '=', $bible->id);
			$content = $query->get();
		} catch (ModelNotFoundException $e) {
			$error = 'Server bibletexts have not been set up correctly yet. Please contant your administrator.';
		}


		return [
			'bibleverses' => collect($searchBvs)->map(function (\StevenBuehner\BibleVerseBundle\Entity\BibleVerse $bv) {
				return ['from' => $bv->getStart(), 'to' => $bv->getEnd()];
			}),
			'bible'       => $bible,
			'verses'      => $content,
			'error'       => $error,
		];

	}

}
