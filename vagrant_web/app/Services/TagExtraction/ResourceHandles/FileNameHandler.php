<?php

namespace App\Services\TagExtraction\ResourceHandles;

use App\Models\File;
use App\Models\Resource;
use App\Services\TagExtraction\Properties\Property;
use App\Services\TagExtraction\Properties\TitleProperty;
use App\Services\TagExtraction\TagExtractionService;
use Doctrine\Common\Collections\Collection;

class FileNameHandler implements HandlerInterface {

	protected $tagExtractionService;

	public function __construct(TagExtractionService $tagExtractionService) {
		$this->tagExtractionService = $tagExtractionService;
	}

	/**
	 * Returns an array of possible metaData
	 *
	 * @param File $resource
	 * @return Collection of Properties
	 */
	public function handle(Resource $resource) {

		$filename = $resource->original_filename;

		// the more commas and other stuff the filename hast, the less likely is it a title
		$title       = trim(pathinfo($filename, PATHINFO_FILENAME));
		$countCommas = substr_count($title, ',') + substr_count($title, ';');
		$titleProp   = new TitleProperty($title, $relevance = max(19 - $countCommas, 0));

		// Extract all Information possible from filename if at least two keywords exist
		$result = $this->tagExtractionService->extractPartsFromStrings($title, 1);

		// The more commas we have, the move likely was it a good Property / Tag
		$result->each(function ($p) use ($countCommas) {
			/** @var $p Property */
			$p->setRelevance(min($countCommas, 19));
		});

		// Add the title to the metaData list
		if (strlen($title) - $countCommas > 0) {
			$result->push($titleProp);
		}

		return $result;
	}
}