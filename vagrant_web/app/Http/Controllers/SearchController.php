<?php

namespace App\Http\Controllers;

use App\Models\Bibleverse;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Resource;
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
		$paginationSize       = 30;
		$bibleVerseExtraction = resolve('BibleVerseService');
		$result               = collect();


		// Resource Type
		if (in_array(strtolower($queryString), Resource::$allResourceTypeKeys)) {
			$result->push(
				[
					'text' => strtoupper($queryString) . '-Typ',
					'icon' => '/img/icons/type_' . strtolower($queryString) . '.svg',
					'item' => [
						'type' => 't',
						'text' => strtolower($queryString)
					]
				]
			);
		}


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


		$restString = trim($bibleVerseExtraction->getLastRestString());

		$resultTotalCount = $result->count();

		if ($resultTotalCount > ($paginationSize * $queryPage)) {
			// Dony Query but limit the $result
			$result = $result->splice(($paginationSize) * ($queryPage - 1), $paginationSize);

			// Eine Suche mit dem Reststring macht nur dann Sinn, wenn es da noch sinnvolle Ergebnisse gibt und der String nicht leer ist
		} else if (strlen($restString) >= 2) {
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
							'text'    => $keyword->title,
							'icon'    => $keyword->icon,
							'item'    => [
								'type' => 'k',
								'id'   => $keyword->id
							],
							'keyword' => $keyword->toArray()
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


	public function guess2(Request $request) {
		/** @var BibleVerseService $bibleVerseExtraction */
		$queryString          = $request->get('q', '');
		$queryString          = str_replace('%', '*', $queryString);
		$queryPage            = $request->get('page', 1);
		$paginationSize       = 15;
		$bibleVerseExtraction = resolve('BibleVerseService');
		$result               = collect();


		// Wildcard Search
		/*
		$result->push(
			[
				'text'  => $queryString,
				'icon'  => '/img/icons/ayce.svg',
				'type'  => '*',
				'query' => [
					'type' => '*',
					'text' => $queryString
				]
			]
		);
		*/


		// Search For Bibleverses
		$verses = $bibleVerseExtraction->stringToBibleVerse($queryString);

		foreach ($verses as $b) {
			$bModel = Bibleverse::findOrNewFromBibleverseInterface($b);
			$result->push(
				[
					'type'  => 'b',
					'query' => [
						'type' => 'b',
						'from' => $bModel->from,
						'to'   => $bModel->to
					],
					'item'  => $bModel
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
							'type'  => 'k',
							'query' => [
								'type' => 'k',
								'id'   => $keyword->id
							],
							'item'  => $keyword
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


	public function guessKeywords(Request $request) {

		$queryString    = $request->get('q', '');
		$queryString    = str_replace('%', '*', $queryString);
		$queryType      = $request->get('t', FALSE);
		$queryPage      = $request->get('page', 1);
		$paginationSize = min((int)$request->get('per_page', 15), 50);

		if ($queryType && !in_array($queryType, array_keys(Keyword::AVAILABLE_TYPES))) {
			$queryType = FALSE;
		}

		// Search for Keywords
		$keywords = Keyword::searchQuery($queryString, $queryType)
			->offset(($paginationSize) * ($queryPage - 1))
			->limit($paginationSize)
			->orderByRaw('LENGTH(title)')
			->get();

		return $keywords;
	}

	public function guessBibleverse(Request $request) {

		$queryString          = $request->get('q', '');
		$bibleVerseExtraction = resolve('BibleVerseService');
		$result               = collect();

		// Search For Bibleverses
		$verses = $bibleVerseExtraction->stringToBibleVerse($queryString);


		foreach ($verses as $verse) {
			$temp = Bibleverse::makeFromBibleverseInterface($verse);
			$temp->setHidden(['created_at', 'updated_at']);

			$result->push($temp);
		}

		return $result;
	}

	public function get(Request $request) {
		$query          = $this->turnRequestIntoQuery($request);
		$paginationSize = min((int)$request->get('per_page', 30), 100);


		/* Pagination funktioniert nur, wenn der Bugfix manuell eingespielt wird in der paginate() Funktion
		$paginationColumns = $this->query->distinct ? $columns : ['*'];
		$results = ($total = $this->toBase()->getCountForPagination($paginationColumns))
			? $this->forPage($page, $perPage)->get($columns)
			: $this->model->newCollection();

		Das kommt hoffentlich in einem der nächsten Updates mit rein:
		https://github.com/laravel/framework/pull/27107
		*/

		return $query->paginate($paginationSize, ['materials.id']);
	}

	protected function turnRequestIntoQuery(Request $request) {
		$searchBars = $request->get('q', []);
		$matQuery   = Material::query()
			->select('materials.*')
			->distinct()
			->with(['author', 'keywords', 'bibleverses', 'resources'])
			->orderBy('materials.rating', 'desc');


		foreach ($searchBars as $index => $bar) {

			if (is_array($bar) && count($bar) > 0) {
				$barGroupColl         = collect($bar)->groupBy('type');
				$keywordIds           = [];
				$bibleverseRanges     = $barGroupColl->get('b', []);
				$resourceTypes        = [];
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

				if ($barGroupColl->has('t')) {
					$resourceTypes = $barGroupColl->get('t')
						->filter(function ($el, $key) {
							return isset($el['text']) && in_array(strtolower($el['text']),
									Resource::$allResourceTypeKeys);
						})
						->map(function ($el) {
							return strtolower($el['text']);
						})
						->all();
				}

				if ($barGroupColl->has('*')) {
					$matchAllStrings = $barGroupColl->get('*')->pluck('text');
				}

				DB::enableQueryLog();
				$matQuery->where(function ($q) use (&$keywordIds, &$bibleverseRanges, &$resourceTypes, &$matchAllStrings, $index) {
					if (count($keywordIds) > 0) {
						$q->orWhereIn("keyword_material{$index}.keyword_id", $keywordIds);
						$q->orWhereIn('materials.author_id', $keywordIds->all());
					}

					// Todo: Validate Bibleverses
					// Todo: Merge bibleverses if they intersect
					if (count($bibleverseRanges) > 0) {
						foreach ($bibleverseRanges as $bv) {
							$from = (int )$bv['from'];
							$to   = (int)$bv['to'];

							$q->orWhereBetween("bibleverses{$index}.from", [$from, $to]);
							$q->orWhereBetween("bibleverses{$index}.to", [$from, $to]);
							$q->orWhere(function ($q) use ($from, $to, $index) {
								$q->where("bibleverses{$index}.from", '>', $from);
								$q->where("bibleverses{$index}.to", '<', $to);
							});
						}
					}


					// Im Resource-Type suchen
					if (count($resourceTypes) > 0) {
						$q->orWhereIn("resources{$index}.type", $resourceTypes);
					}

					// Im Titel suchen
					if (count($matchAllStrings) > 0) {
						foreach ($matchAllStrings as $string) {
							// ToDo: Check if $string is Querry-Injection-Save!
							$q->orWhere('materials.title', 'like', "%$string%");
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
					$matQuery->join("bibleverses as bibleverses{$index}",
						"bibleverse_material{$index}.bibleverse_id", '=',
						"bibleverses{$index}.id");
				}

				if (count($resourceTypes) > 0) {
					/** @var Builder $matQuery */
					$matQuery->leftJoin("material_resource as material_resource{$index}", 'materials.id', '=',
						"material_resource{$index}.material_id");
					$matQuery->join("resources as resources{$index}",
						"material_resource{$index}.resource_id", '=',
						"resources{$index}.id");
				}
			}
		}

		return $matQuery;
	}

}
