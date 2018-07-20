<?php

namespace App\Http\Controllers;

use App\Models\Bibleverse;
use App\Models\Keyword;
use App\Models\Material;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

class SearchController extends Controller {

	public function __construct() {
		$this->middleware('auth');
	}

	public function index() {
		return view('search.index');
	}

	public function guess(Request $request) {
		/** @var BibleVerseService $bibleVerseExtraction */
		$queryString          = $request->get('q', '');
		$queryString          = str_replace('%', '*', $queryString);
		$queryPage            = $request->get('page', 1);
		$paginationSize       = 15;
		$bibleVerseExtraction = resolve('BibleVerseService');
		$result               = collect();


		// Wildcard Search
		$result->push(
			[
				'text' => $queryString,
				'icon' => '/img/icons/ayce.svg',
				'item' => [
					'type' => '*',
					'text' => $queryString
				]
			]
		);


		// Search For Bibleverses
		$verses = $bibleVerseExtraction->stringToBibleVerse($queryString);

		foreach ($verses as $b) {
			$bModel = Bibleverse::findOrNewFromBibleverseInterface($b);
			$result->push(
				[
					'text' => $bModel->label,
					'icon' => $bModel->icon,
					'item' => [
						'type' => 'b',
						// 'id'   => $bModel->id,
						'from' => $bModel->from,
						'to'   => $bModel->to
					]
				]
			);
		}


		$restString = $bibleVerseExtraction->getLastRestString();

		$resultTotalCount = $result->count();

		if ($resultTotalCount > ($paginationSize * $queryPage)) {
			// Dony Query but limit the $result
			$result = $result->splice(($paginationSize) * ($queryPage - 1), $paginationSize);
		} else {
			$takeFromResult = max(0, $resultTotalCount - $paginationSize * ($queryPage - 1));
			$takeFromQuery  = $paginationSize - $takeFromResult;

			if ($takeFromResult > 0) {
				$result = $result->splice(($paginationSize) * ($queryPage - 1), $takeFromResult);
			} else {
				$result = collect();
			}

			if ($takeFromQuery > 0) {
				$offset = max(0, ($paginationSize * ($queryPage - 1)) - $resultTotalCount);

				// Search for Keywords
				$query = Keyword::searchQuery($restString)
								->offset($offset)
								->limit($takeFromQuery)
								->get();

				$query->each(function (Keyword $keyword) use ($result) {
					$result->push(
						[
							'text' => $keyword->title,
							'icon' => $keyword->icon,
							'item' => [
								'type' => 'k',
								'id'   => $keyword->id
							]
						]
					);
				});
			}
		}


		$paginator = new Paginator($result, $paginationSize, $queryPage);
		$paginator->hasMorePagesWhen($result->count() == $paginationSize);
		$paginator->setPath(url()->current());

		return $paginator;
	}

	public function get(Request $request) {
		$query = $this->turnRequestIntoQuery($request);

		return $query->paginate(20);
	}

	protected function turnRequestIntoQuery(Request $request) {
		$searchBars = $request->get('q', []);
		$matQuery   = Material::query()
							  ->select('materials.*')
							  ->distinct()
							  ->with(['author', 'keywords', 'bibleverses'])
							  ->orderBy('materials.rating', 'desc');


		foreach ($searchBars as $index => $bar) {

			if (is_array($bar) && count($bar) > 0) {
				$barGroupColl         = collect($bar)->groupBy('type');
				$keywordIds           = [];
				$bibleverseRanges     = $barGroupColl->get('b', []);
				$matchAllStrings      = [];
				$keywordsAvailable    = FALSE;
				$bibleversesAvailable = FALSE;


				if ($barGroupColl->has('k')) {
					$keywordIds = $barGroupColl->get('k')->pluck('id');
					$keywordIds->unique();
					$keywordsAvailable = TRUE;
				}

				if (count($bibleverseRanges) > 0) {
					$bibleversesAvailable = TRUE;
				}

				if ($barGroupColl->has('*')) {
					$matchAllStrings = $barGroupColl->get('*')->pluck('text');
				}


				DB::enableQueryLog();
				$matQuery->where(function ($q) use (&$keywordIds, &$bibleverseRanges, &$matchAllStrings, $index) {
					if (count($keywordIds) > 0) {
						$q->orWhereIn("keyword_material{$index}.keyword_id", $keywordIds);
					}

					// Todo: Validate Bibleverses
					// Todo: Merge bibleverses if they intersect
					if (count($bibleverseRanges) > 0) {
						foreach ($bibleverseRanges as $bv) {
							$from = (int ) $bv['from'];
							$to   = (int) $bv['to'];

							$q->orWhereBetween("bibleverses{$index}.from", [$from, $to]);
							$q->orWhereBetween("bibleverses{$index}.to", [$from, $to]);
							$q->orWhere(function ($q) use ($from, $to, $index) {
								$q->where("bibleverses{$index}.from", '>', $from);
								$q->where("bibleverses{$index}.to", '<', $to);
							});
						}
					}
				});

				if ($keywordsAvailable === TRUE) {
					/** @var Builder $matQuery */
					$matQuery->leftJoin("keyword_material as keyword_material{$index}", 'materials.id', '=',
										"keyword_material{$index}.material_id");
				}

				if ($bibleversesAvailable === TRUE) {
					/** @var Builder $matQuery */
					// Todo: Kann hier evt. eine der beiden left Joins ohne das index auskommen?
					$matQuery->leftJoin("bibleverse_material as bibleverse_material{$index}", 'materials.id', '=',
										"bibleverse_material{$index}.material_id");
					$matQuery->leftJoin("bibleverses as bibleverses{$index}",
										"bibleverse_material{$index}.bibleverse_id", '=',
										"bibleverses{$index}.id");
				}
			}
		}

		return $matQuery;
	}


	protected function getOrWhereFromItem($query, $item) {
		if (is_array($item) && isset($item['type'])) {
			switch ($item['type']) {
				case '*':
					// Search in all (wildcard)
					break;
				case 'k':
					// Keyword
					break;
				case 'b':
					// Bibleverse
					break;

			}
		}
	}

}
