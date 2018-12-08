<?php

namespace App\Http\Controllers\Api;

use App\Models\Bibleverse;
use App\Models\PdfFile;
use App\Services\ResourceHandling\PdfHandlingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

class PdfTagExtractionController extends BaseController {

	protected $pdfHandlingService;
	protected $bibleVerseService;

	public function __construct(PdfHandlingService $pdfHandlingService, BibleVerseService $bibleVerseService) {
		$this->middleware(['auth:api']);
		$this->pdfHandlingService = $pdfHandlingService;
		$this->bibleVerseService  = $bibleVerseService;
	}

	public function extractTags(PdfFile $resource, Request $request) {

		$pages  = $request->get('pages', []);
		$ranges = $this->getPageRanges($pages);

		$texts = [];
		if (count($ranges) === 0) {
			$texts[] = $this->pdfHandlingService->pdfToText($resource);
		} else {
			foreach ($ranges as $range) {
				list($from, $to) = $range;
				$texts[] = $this->pdfHandlingService->pdfToText($resource, $from, $to);
			}
		}

		$foundBibleverses = collect();
		foreach ($texts as $t) {
			$found            = $this->bibleVerseService->stringToBibleVerse($t);
			$foundBibleverses = $foundBibleverses->merge($found);
		}

		$test        = $foundBibleverses->toArray();
		$bibleverses = $this->bibleVerseService->mergeBibleverses($test);
		$bvModels    = [];

		/** @var \StevenBuehner\BibleVerseBundle\Entity\BibleVerse[] $bibleverses */
		foreach ($bibleverses as $bv) {
			$bvModels[] = Bibleverse::findOrNewFromBibleverseInterface($bv);
		}

		return $bvModels;
	}

	protected function getPageRanges($pages) {

		$ranges = [];

		if (!is_array($pages)) {
			$pages = [];
		} else {
			foreach ($pages as $i => $page) {
				$pages[$i] = (int) $page;
			}

			asort($pages);

			$firstNumber = array_shift($pages);
			$ranges[]    = [$firstNumber, $firstNumber]; // initial value

			foreach ($pages as $page) {
				$range    = array_pop($ranges);
				$extend   = ($range[1] == $page - 1);
				$ranges[] = [$range[0], $extend ? $page : $range[1]];
				if (!$extend) {
					$ranges[] = [$page, $page];
				}
			}
		}

		return $ranges;

	}


}
