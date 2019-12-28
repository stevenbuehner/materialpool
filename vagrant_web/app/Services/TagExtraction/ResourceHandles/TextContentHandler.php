<?php

namespace App\Services\TagExtraction\ResourceHandles;

use App\Models\File;
use App\Models\Resource;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use App\Services\TagExtraction\Properties\BibleverseProperty;
use App\Services\TagExtraction\Properties\OcrTextProperty;
use App\Services\TagExtraction\Properties\Property;
use App\Services\TagExtraction\TagExtractionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

class TextContentHandler implements HandlerInterface {

	protected $tagExtractionService;
	protected $bibleVerseService;

	public function __construct(TagExtractionService $tagExtractionService, BibleVerseService $bibleVerseService) {
		$this->tagExtractionService = $tagExtractionService;
		$this->bibleVerseService    = $bibleVerseService;
	}

	/**
	 * Returns an array of possible metaData
	 *
	 * @param File $resource
	 * @return Collection of Properties
	 */
	public function handle(Resource $resource) {
		$result = new Collection();

		$firstLineResults = $this->searchInFirstLine($resource);
		$result           = $result->merge($firstLineResults);

		// Eine Property ist auf jeden Fall OCR
		// Also mindestens drei Tags werden gefordert
		$firstLineIsRemoved = $firstLineResults->count() > 4;
		if ($firstLineIsRemoved) {
			$this->removeFirstLine($resource);
		}

		$result = $result->merge($this->searchInEveryLine($resource, !$firstLineIsRemoved));
		$unique = $result->unique();

		return $unique;
	}

	/**
	 * @param Resource $resource
	 * @return Collection
	 */
	protected function searchInFirstLine(Resource $resource) {
		$result = new Collection();

		if ($resource instanceof TextContentInterface) {

			// get Content
			$content = $resource->getContent();

			// Was there a firstLine backed up previously?
			$firstLine = $resource->getFirstLine();

			// Use the first line in textfiles to find any tags
			if ($firstLine === FALSE) {
				$firstLine = strtok($content, "\n");
			}

			$foundTags = $this->tagExtractionService->extractPartsFromStrings($firstLine, 2, $context = ['firstline']);

			// How many tags where found in the first line of text? => At least three are needed, to identify this as info
			if ($foundTags->count() < 3) {
				Log::info("Too less information was extracted from the first line -> ignoring information",
					['resource_id' => $resource->id, 'handler' => __CLASS__]);

				// Use the whole Textfile as OCR-Information
				$otherLines = trim($content);
				if (strlen($otherLines) > 3) {
					$ocrProperty = new OcrTextProperty(Str::limit($content, 200),
						RelevanceInterface::RELEVANCE_EXIF_MAX - 10);
					$result->push($ocrProperty);
				}

			} else {
				// Max relevance, because used added it
				$relevance = RelevanceInterface::RELEVANCE_USER_MAX;
				$foundTags->each(function (Property $property) use ($relevance) {
					$property->setRelevance($relevance);
				});
				$result = $result->merge($foundTags);
			}

		} else {
			Log::error('This file is not of mimetype text/plain.', ['resource_id' => $resource->id]);

			return $result;
		}

		return $result;
	}

	protected function removeFirstLine(Resource $resource) {
		if ($resource instanceof TextContentInterface && $resource->getFirstLine() === FALSE) {

			// get Content
			$content = $resource->getContent();

			// Delete the first line in the $content
			$firstLine        = strtok($content, "\n");
			$withoutFirstLine = $this->removeFirstLineFromString($content);

			$resource->setFirstLine($firstLine);
			$resource->setContent($withoutFirstLine);

			$resource->save();
		} else {
			Log::error('This file is not of mimetype text/plain. Could not delete FirstContentLine',
				['resource_id' => $resource->id]);
		}
	}

	protected function removeFirstLineFromString($string) {
		// Delete the first line in the $content
		return preg_replace('/^.+\n/', '', $string);
	}

	protected function searchInEveryLine(Resource $resource, $ignoreFirstLine = FALSE) {
		$result = new Collection();

		if ($resource instanceof TextContentInterface) {

			// get Content
			$content = $resource->getContent();

			if ($ignoreFirstLine === TRUE) {
				$content = $this->removeFirstLineFromString($content);
			}

			// Extract bibleverses
			$foundBibleVerses = $this->bibleVerseService->stringToBibleVerse($content);

			// Transform BibleVerseInterface to BibleverseProperty
			$result = collect($foundBibleVerses)->map(function (BibleVerseInterface $bv) {
				$b = new BibleverseProperty($bv);
				$b->setRelevance(RelevanceInterface::RELEVANCE_EXIF_MIN);

				return $b;
			});

			// Use this Ocr-Text only (MIN-Relevance) if the searchInFirstLine got less than 3 Keywords => use whole text
			$ocrProperty = new OcrTextProperty(Str::limit($content, 200), RelevanceInterface::RELEVANCE_EXIF_MIN);
			$result->push($ocrProperty);

		} else {
			Log::error('This file is not of mimetype text/plain.', ['resource_id' => $resource->id]);

			return $result;
		}

		return $result;
	}
}