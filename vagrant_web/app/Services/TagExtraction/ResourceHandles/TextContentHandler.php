<?php

namespace App\Services\TagExtraction\ResourceHandles;

use App\Models\File;
use App\Models\Resource;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use App\Services\TagExtraction\Properties\OcrTextProperty;
use App\Services\TagExtraction\Properties\Property;
use App\Services\TagExtraction\TagExtractionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TextContentHandler implements HandlerInterface {

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
		$result = new Collection();

		if ($resource instanceof TextContentInterface) {

			// get Content
			$content = $resource->getContent();

			// Use the first line in textfiles to find any tags
			$firstLine = strtok($content, "\n");
			$foundTags = $this->tagExtractionService->extractPartsFromStrings($firstLine, 2, $context = ['firstline']);

			// How many tags where found? => At least three are needed, to identify this as info
			if ($foundTags->count() < 3) {
				Log::info("Too less information was extracted from the first line -> ignoring information",
						  ['resource_id' => $resource->id, 'handler' => __CLASS__]);

				// Use the whole Textfile as OCR-Information
				$otherLines = trim($content);
				if (strlen($otherLines) > 3) {
					$ocrProperty = new OcrTextProperty($content, RelevanceInterface::RELEVANCE_EXIF_MAX);
					$result->push($ocrProperty);
				}

			} else {
				// The more Tags where found, the more relevant they are
				$relevance = min(RelevanceInterface::RELEVANCE_USER_MIN + $foundTags->count(),
								 RelevanceInterface::RELEVANCE_USER_MAX);
				$foundTags->each(function (Property $property) use ($relevance) {
					$property->setRelevance($relevance);
				});
				$result = $result->merge($foundTags);


				// Create a Ocr-Text-Property from anything BUT the first line
				$otherLines = trim(substr($content, strlen($firstLine)));
				if (strlen($otherLines) > 3) {
					$ocrProperty = new OcrTextProperty($otherLines, RelevanceInterface::RELEVANCE_EXIF_MAX);
					$result->push($ocrProperty);
				}
			}
		} else {
			Log::error('This file is not of mimetype text/plain.', ['resource_id' => $resource->id]);

			return $result;
		}

		return $result;
	}
}