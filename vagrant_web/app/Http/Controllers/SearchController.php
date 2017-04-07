<?php

namespace App\Http\Controllers;

use App\Models\Bibleverse;
use App\Models\Keyword;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

class SearchController extends Controller {

	public function index() {
		return view('search.index');
	}

	public function guess(Request $request) {
		/** @var BibleVerseService $bibleVerseExtraction */
		$queryString          = $request->get('q', '');
		$queryPage            = $request->get('page', 1);
		$paginationSize       = 5;
		$bibleVerseExtraction = resolve('BibleVerseService');
		$result               = collect();


		// Wildcard Search
		$result->push(
			[
				'text' => '*' . $queryString . '*',
				'icon' => '/img/icons/ayce.svg',
				'item' => [
					'type' => '*'
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
						'id'   => $bModel->id,
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

}
