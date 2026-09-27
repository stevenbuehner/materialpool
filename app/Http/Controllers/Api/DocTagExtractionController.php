<?php

namespace App\Http\Controllers\Api;

use App\Models\Bibleverse;
use App\Models\DocumentFile;
use App\Services\ResourceHandling\DocHandlingService;
use App\Services\ResourceHandling\PdfHandlingService;
use Illuminate\Http\Request;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

class DocTagExtractionController extends PdfTagExtractionController {

	protected $docHandlingService;

	public function __construct(PdfHandlingService $pdfHandlingService, BibleVerseService $bibleVerseService, DocHandlingService $docHandlingService) {
		parent::__construct($pdfHandlingService, $bibleVerseService);
		$this->docHandlingService = $docHandlingService;
	}

	public function extractDocumentTags(DocumentFile $resource, Request $request) {

		$pages  = $request->get('pages', []);
		$ranges = $this->getPageRanges($pages);

		$texts = [];
		if (count($ranges) === 0) {
			$texts[] = $this->docHandlingService->documentResourceToText($resource);
		} else {
			foreach ($ranges as $range) {
				list($from, $to) = $range;
				$texts[] = $this->docHandlingService->documentResourceToText($resource, $from, $to);
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


}
