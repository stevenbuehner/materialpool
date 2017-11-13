<?php

namespace App\Services\TagExtraction\ResourceHandles;

use App\Models\File;
use App\Models\Person;
use App\Models\Resource;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use App\Services\TagExtraction\Properties\AuthorProperty;
use App\Services\TagExtraction\Properties\KeywordProperty;
use App\Services\TagExtraction\Properties\Property;
use App\Services\TagExtraction\Properties\TitleProperty;
use App\Services\TagExtraction\TagExtractionService;
use App\Services\TagExtraction\TagRecognition\ExifDate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Metadata\MetadataBag;
use PHPExiftool\Driver\Value\ValueInterface;
use PHPExiftool\Reader;

class FileExifHandler implements HandlerInterface {

	protected $tagExtractionService;
	protected $exifReader;

	public function __construct(TagExtractionService $tagExtractionService, Reader $exifReader) {
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


		$fileEntity = $this->exifReader->reset()->files($localAbsolutePath)->first();
		/** @var MetadataBag $metaData */
		$metaData = $fileEntity->getMetadatas();

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

		return $result;
	}


	/**
	 * @param MetadataBag $metaDataBag
	 * @return Collection
	 */
	protected function getTagsFromExifTitle(MetadataBag $metaDataBag) {
		$result = new Collection();
		$titles = $this->allMatchesValuesForKeys($metaDataBag, ['title', 'Title', 'Subject']);

		foreach ($titles as $title) {
			// Remove Endings with Filetype in the title (some Programms use the document-name as title, including suffix)
			$title = preg_replace('~\s*\.(pdf|docx?|xml)$~i', '', $title);

			$result->push(new TitleProperty($title, RelevanceInterface::RELEVANCE_EXIF_MIN));
		}

		// Sort by length (the longer the better ;-)
		$result = $result->sortByDesc(function (TitleProperty $tp) {
			return strlen($tp->getValue());
		});

		return $result;
	}

	/**
	 * All String-Values found for the given keys. Dublicate and empty values have been removed.
	 *
	 * @param MetadataBag $metaDataBag
	 * @param string[]    $keys
	 * @return String[]
	 */
	protected function allMatchesValuesForKeys(MetadataBag $metaDataBag, $keys) {
		$result = [];

		/** @var Metadata $metadata */
		foreach ($metaDataBag as $metadata) {
			if (in_array($metadata->getTag()->getName(), $keys)) {

				// Key found
				switch ($metadata->getValue()->getType()) {
					case ValueInterface::TYPE_MONO:
						$value = trim($metadata->getValue()->asString());

						if (!empty($value) && !in_array($value, $result)) {
							$result[] = $value;
						}

						break;

					case ValueInterface::TYPE_MULTI:
						// Use the whole string in Multi and Mono-Types (Bibleverses with comma would otherwise be split after chapter)
						foreach ($metadata->getValue()->asArray() as $value) {
							$value = trim($value);

							if (!empty($value) && !in_array($value, $result)) {
								$result[] = $value;
							}
						}

						break;

					case ValueInterface::TYPE_BINARY:
					default:
				}
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
	 * @param MetadataBag $metaDataBag
	 * @return Collection
	 */
	protected function getTagsFromExifComment(MetadataBag $metaDataBag) {
		$allMatches = $this->allMatchesValuesForKeys($metaDataBag, ['Comments', 'comments']);
		$tags       = $this->tagExtractionService->extractPartsFromStrings($allMatches, 2, $context = ['exif']);
		$this->setRelevance($tags, RelevanceInterface::RELEVANCE_EXIF_MAX);

		return $tags;
	}

	/**
	 * Set the relevance/priority for all RelevanceInterface instances in $metaData array
	 *
	 * @param Collection &$propertiesCollection
	 * @param int        $relevance
	 * @return Collection
	 */
	protected function setRelevance(Collection &$propertiesCollection, $relevance) {
		$propertiesCollection->each(function ($item) use ($relevance) {
			$item->setRelevance($relevance);
		});

		return $propertiesCollection;
	}

	/**
	 * @param MetadataBag $metaDataBag
	 * @return Collection
	 */
	protected function getTagsFromExifKeywords(MetadataBag $metaDataBag) {

		// First try AppleKeywords. This will properly recognize commas in Keywords as one Keyword (i.e. with bibleverses)
		// If nothing was found => use the normal Keywords
		$allMatches = $this->allMatchesValuesForKeys($metaDataBag, ['AppleKeywords']);
		if (count($allMatches) > 0) {
			// Human Input Prio
			$exifPrio = RelevanceInterface::RELEVANCE_USER_MIN;
		} else {
			$allMatches = $this->allMatchesValuesForKeys($metaDataBag, ['Keywords']);

			// Default Exif-Prio
			$exifPrio = RelevanceInterface::RELEVANCE_USER_MAX;
		}

		$tags = $this->tagExtractionService->extractPartsFromStrings($allMatches, 1, $context = ['exif']);
		$this->setRelevance($tags, $exifPrio);

		return $tags;
	}

	/**
	 * @param MetadataBag $metaDataBag
	 * @return Collection
	 */
	protected function getTagsFromExifAuthor(MetadataBag $metaDataBag) {
		$result     = new Collection();
		$allMatches = $this->allMatchesValuesForKeys($metaDataBag, ['Author', 'Creator', 'By-line']);
		$allNames   = $this->getCombinedSplitValues($allMatches);

		foreach ($allNames as $authorName) {

			// Only add high quality names
			// Todo put the names into config
			if (!in_array(strtolower($authorName), ['unknown', '', 'unbekannt', 'nobody'])) {
				$result->push(new AuthorProperty($authorName, RelevanceInterface::RELEVANCE_EXIF_MAX));
				$result->push(new KeywordProperty($authorName, Person::class, RelevanceInterface::RELEVANCE_EXIF_MAX));
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

	/**
	 * @param MetadataBag $metaDataBag
	 * @return Collection
	 */
	protected function getTagsFromExifCreateDate(MetadataBag $metaDataBag) {
		$result            = new Collection();
		$match             = $this->allMatchesValuesForKeys($metaDataBag, ['CreateDate']);
		$exifDateExtractor = new ExifDate();

		foreach ($match as $cDate) {
			$tags = $exifDateExtractor->extractSpecializedTag('CreateDate: ' . $cDate);

			if (count($tags) === 1) {
				/** @var Property $createTag */
				$createTag = array_shift($tags);
				$createTag->setRelevance(RelevanceInterface::RELEVANCE_EXIF_MAX);
				$result->push($createTag);
			}
		}

		return $result;
	}

	/**
	 * Returns the best match of the requested keys ... currently the first found element or $default
	 *
	 * @param MetadataBag $metaDataBag
	 * @param string[]    $keys
	 * @param mixed       $default return value
	 * @return mixed
	 */
	protected function bestMatchValueForKeys($metaDataBag, $keys, $default = '') {

		$allResults = $this->allMatchesValuesForKeys($metaDataBag, $keys);

		if (count($allResults) > 0) {
			return array_shift($allResults);
		} else {
			return $default;
		}
	}

}
