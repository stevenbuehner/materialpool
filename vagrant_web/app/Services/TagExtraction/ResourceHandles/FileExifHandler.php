<?php

namespace App\Services\TagExtraction\ResourceHandles;

use App\Models\File;
use App\Models\Resource;
use App\Services\ExifReader\ExifReaderInterface;
use App\Services\TagExtraction\Interfaces\PropertyInterface;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use App\Services\TagExtraction\Properties\AuthorProperty;
use App\Services\TagExtraction\Properties\CreateDateProperty;
use App\Services\TagExtraction\Properties\KeywordProperty;
use App\Services\TagExtraction\Properties\TitleProperty;
use App\Services\TagExtraction\TagExtractionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPExif\Exif;

class FileExifHandler implements HandlerInterface {

	protected $tagExtractionService;
	protected $exifReader;

	public function __construct(TagExtractionService $tagExtractionService, ExifReaderInterface $exifReader) {
		$this->tagExtractionService = $tagExtractionService;
		$this->exifReader           = $exifReader;
	}

	/**
	 * Returns an array of possible metaData
	 *
	 * @param File $resource
	 * @return Collection of Properties
	 */
	public function handle(Resource $resource) {

		if (empty($resource->local_path)) {
			// First needs a download of the remote Resource!
			Log::error('This handler can only applied to local files!',
				['resource_id' => $resource->id, 'handler' => __CLASS__]);

			// TODO: Download remote Files to local
		}

		list($diskName, $localRelativePath) = $resource->getLocalStorageAndPath();

		/** @var Collection $result */
		$result = new Collection();

		$localDisk         = Storage::disk($diskName);
		$localPathPrefix   = $localDisk->getDriver()->getAdapter()->getPathPrefix();
		$localAbsolutePath = $localPathPrefix . $localRelativePath;

		try {
			$metaData = $this->exifReader->read($localAbsolutePath);

			$allResultProperties = $this->getTagsFromExifTitle($metaData);
			$result              = $result->merge($allResultProperties);

			// Search in comments
			$allResultProperties = $this->getTagsFromExifComment($metaData);
			$result              = $result->merge($allResultProperties);

			// Search for keywords
			$allResultProperties = $this->getTagsFromExifKeywords($metaData);
			$result              = $result->merge($allResultProperties);

			// Author
			$allResultProperties = $this->getTagsFromExifAuthor($metaData);
			$result              = $result->merge($allResultProperties);

			// CreateDate
			$allResultProperties = $this->getTagsFromExifCreateDate($metaData);
			$result              = $result->merge($allResultProperties);

			$result->unique();

			$result = $this->filterIgnorePatterns($result);

		} catch (\Exception $e) {
			Log::error($e->getMessage());
		}

		return $result;
	}


	/**
	 * @param Exif $exifData
	 * @return Collection
	 */
	protected function getTagsFromExifTitle(Exif $exifData): Collection {
		$result = new Collection();

		$title = $exifData->getTitle();

		if ($title !== FALSE) {
			// Remove Endings with Filetype in the title (some Programms use the document-name as title, including suffix)
			$title = preg_replace('~\s*\.(pdf|docx?|xml)$~i', '', $title);

			$result->push(new TitleProperty($title, RelevanceInterface::RELEVANCE_EXIF_MAX));
		}

		// Sort by length (the longer the better ;-)
		/*
		$result = $result->sortByDesc(function (TitleProperty $tp) {
			return strlen($tp->getValue());
		});
		*/

		return $result;
	}

	/**
	 * All String-Values found for the given keys. Dublicate and empty values have been removed.
	 *
	 * @param Exif $exifData
	 * @param string[] $keys
	 * @return String[]
	 */
	protected function allMatchesValuesForKeys(Exif $exifData, $keys) {
		$result = [];


		foreach ($exifData->getRawData() as $exifName => $value) {

			if (in_array($exifName, $keys)) {

				$result[] = $value;

				continue;

			}
		}

		foreach ($result as $id => $value) {
			$value = str_replace("\n", '', $value);

			// Check if string is base64 encoded. If yes => decode
			$dec = base64_decode($value);

			if (base64_encode($dec) === $value) {
				// $value was encoded => ignore it (for now)
				Log::info('Base64 Encoded Information will be ignored: ' . $value);
				unset($result[$id]);
			}
		}


		return $result;
	}

	/**
	 * @param Exif $exifData
	 * @return Collection
	 */
	protected function getTagsFromExifComment(Exif $exifData): Collection {

		$tags    = new Collection();
		$caption = $exifData->getCaption();

		if ($caption !== FALSE) {
			$tags = $this->tagExtractionService->extractPartsFromStrings([$caption], 2, $context = ['exif']);
			$this->setRelevance($tags, RelevanceInterface::RELEVANCE_EXIF_MAX);
		}

		return $tags;
	}

	/**
	 * Set the relevance/priority for all RelevanceInterface instances in $metaData array
	 *
	 * @param Collection &$propertiesCollection
	 * @param int $relevance
	 * @return Collection
	 */
	protected function setRelevance(Collection &$propertiesCollection, $relevance) {
		$propertiesCollection->each(function ($item) use ($relevance) {
			$item->setRelevance($relevance);
		});

		return $propertiesCollection;
	}

	/**
	 * @param Exif $exifData
	 * @return Collection
	 */
	protected function getTagsFromExifKeywords(Exif $exifData): Collection {

		$tags = new Collection();

		// First try AppleKeywords. This will properly recognize commas in Keywords as one Keyword (i.e. with bibleverses)
		// If nothing was found => use the normal Keywords
		$keywords = $exifData->getKeywords();

		if ($keywords !== FALSE) {
			// Default Exif-Prio
			$exifPrio = RelevanceInterface::RELEVANCE_USER_MAX;

			$tags = $this->tagExtractionService->extractPartsFromStrings([$keywords], 1, $context = ['exif']);
			$this->setRelevance($tags, $exifPrio);
		}

		return $tags;
	}


	/**
	 * @param Exif $exifData
	 * @return Collection
	 */
	protected function getTagsFromExifAuthor(Exif $exifData): Collection {

		$result = new Collection();

		$author = $exifData->getAuthor();

		if ($author !== FALSE) {
			$pattern = config('tagging.exif.author.ignore.patterns', []);
			$values  = config('tagging.exif.author.ignore.values', []);

			// Only add high quality names
			if (
				!$this->doesTagMatchIgnorePattern($author, $pattern) &&
				!($this->doesTagMatchIgnoreValue($author, $values))
			) {
				$result->push(new AuthorProperty($author, RelevanceInterface::RELEVANCE_EXIF_MAX));
				$result->push(new KeywordProperty($author, 'person', RelevanceInterface::RELEVANCE_EXIF_MAX));
			}
		}

		return $result;
	}

	/**
	 * Split each value for commas and semikolons and join all parts to one array with dublicates and empty lines
	 * removed.
	 *
	 * @param string|string[] $values
	 * @return string[]
	 */
	protected function getCombinedSplitValues($values) {
		if (!is_array($values)) {
			$values = [$values];
		}

		$result = [];
		foreach ($values as $stringValue) {
			$splitValues = preg_split('~ *[,;]+ *~', $stringValue);

			foreach ($splitValues as $val) {
				$val = trim($val);

				if (strlen($val) > 0 && !in_array($val, $result)) {
					$result [] = $val;
				}
			}
		}

		return $result;
	}

	protected function doesTagMatchIgnorePattern($tagText, $allPaterns) {
		foreach ($allPaterns as $pattern) {
			if (preg_match($pattern, $tagText) === 1) {
				return TRUE;
			}
		}

		return FALSE;
	}

	protected function doesTagMatchIgnoreValue($tagText, &$allBlacklistValues) {
		return in_array($tagText, $allBlacklistValues);
	}

	/**
	 * @param Exif $exifData
	 * @return Collection
	 */
	protected function getTagsFromExifCreateDate(Exif $exifData): Collection {

		$result = new Collection();
		$match  = $exifData->getCreationDate();

		if ($match !== FALSE) {
			$tag = new CreateDateProperty($match, RelevanceInterface::RELEVANCE_EXIF_MAX);
			$result->push($tag);
		}

		return $result;
	}

	protected function filterIgnorePatterns(Collection $collection) {

		return $collection->reject(function (PropertyInterface $property) {
			if ($property instanceof KeywordProperty) {
				$value    = $property->getValue();
				$patterns = config('tagging.exif.keywords.ignore.patterns', []);
				$values   = config('tagging.exif.keywords.ignore.values', []);

				return $this->doesTagMatchIgnorePattern($value, $patterns) || $this->doesTagMatchIgnoreValue($value,
						$values);

			} else if ($property instanceof AuthorProperty) {
				return FALSE;
				// This has been done before already, hasn't it?

				$value    = $property->getValue();
				$patterns = config('tagging.exif.author.ignore.patterns', []);
				$values   = config('tagging.exif.author.ignore.values', []);

				return $this->doesTagMatchIgnorePattern($value, $patterns) || $this->doesTagMatchIgnoreValue($value,
						$values);
			}


			return FALSE;
		});
	}

	/**
	 * Returns the best match of the requested keys ... currently the first found element or $default
	 *
	 * @param Exif $exifData
	 * @param string[] $keys
	 * @param mixed $default return value
	 * @return mixed
	 */
	protected function bestMatchValueForKeys($exifData, $keys, $default = '') {

		$allResults = $this->allMatchesValuesForKeys($exifData, $keys);

		if (count($allResults) > 0) {
			return array_shift($allResults);
		} else {
			return $default;
		}
	}


}
