<?php

namespace App\Services\TagExtraction;

use App\Models\Keyword;
use App\Services\TagExtraction\Interfaces\CompareablePropertyInterface;
use App\Services\TagExtraction\Interfaces\PreRecognitionProcessInterface;
use App\Services\TagExtraction\Interfaces\TagRecognitionInterface;
use App\Services\TagExtraction\Properties\BibleverseProperty;
use Illuminate\Support\Collection;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

class TagExtractionService {
	protected $bibleVerseService;
	protected $recognitionFolder     = NULL;
	protected $recognitionNamespace  = NULL;
	protected $tagRecognitionLibrary = NULL;
	protected $preRecognitionLibrary = NULL;

	public function __construct(BibleVerseService $bibleVerseService) {
		$this->bibleVerseService = $bibleVerseService;

		$this->recognitionFolder    = dirname(__FILE__) . '/TagRecognition/';
		$this->recognitionNamespace = 'App\Services\TagExtraction\TagRecognition';
	}

	/**
	 *
	 * @param string|array<string> $strings
	 * @param int   $numRequiredCommasForResult (Default = 2)
	 * @param array $context (Data, that may be passed to the tagRecognition etc.)
	 * @return Collection
	 */
	public function extractPartsFromStrings($strings, $numRequiredCommasForResult = 2, $context = []) {
		if (!is_array($strings)) {
			$strings = [
				$strings
			];
		}

		$result = new Collection();

		foreach ($strings as $key => $str) {
			list($preProcessedString, $preTagCollection, $allowTagRecognition) = $this->runPreRecognitionTasks($str,
																											   $context);

			/** @var $preTagCollection Collection */
			if ($preTagCollection->count() > 0) {
				$result = $result->merge($preTagCollection);
			}

			if (FALSE === $allowTagRecognition) {
				continue;
			}

			// Extract bibleverses (they might have ',' in it and need to processed before tagging etc.)
			$foundBibleVerses = $this->bibleVerseService->stringToBibleVerse($preProcessedString);
			$bvResult         = collect($foundBibleVerses)->map(function (BibleVerseInterface $bv) {
				return new BibleverseProperty($bv);
			});
			$result           = $result->merge($bvResult);

			$bibleVerseRemainString = $this->bibleVerseService->getLastRestString();


			// Recognize Text-Parts
			// Split all strings at ";" and "," and add all of them to $stingChunks
			$stringChunks = [];
			$stringValues = preg_split('~ *[,;]+ *~', $bibleVerseRemainString);

			// Extract special tags
			if (count($stringValues) >= $numRequiredCommasForResult) {
				foreach ($stringValues as $val) {
					if (strlen(trim($val)) > 0) {
						$stringChunks [] = $val;
						$foundTags       = $this->recognizeTagsFromSingleString($val);

						/** @var $foundTags Collection */
						if ($foundTags->count() > 0) {
							$result = $result->merge($foundTags);
						}
					}
				}
			}
		}

		$result->unique();

		return $result;
	}

	/**
	 * @param $stringInput
	 * @param $context
	 * @return array
	 */
	protected function runPreRecognitionTasks($stringInput, &$context) {
		$allowTagRecognition = TRUE;
		$tagCollection       = new Collection();

		foreach ($this->getPreRecognitionLibrary() as $process) {
			list($stringInput, $tempTags) = $process->preProcessInput($stringInput, $context);

			if (count($tempTags) > 0) {
				$tagCollection = $tagCollection->merge($tempTags);

				if ($process->allowTagRecognitionAfterThis() === FALSE) {
					$allowTagRecognition = FALSE;
				}
			}

		}

		return [$stringInput, $tagCollection, $allowTagRecognition];
	}

	/**
	 * @return PreRecognitionProcessInterface[]
	 */
	protected function getPreRecognitionLibrary() {
		if (NULL === $this->tagRecognitionLibrary) {
			$this->setUpRecognitionLibraries();
		}

		return $this->preRecognitionLibrary;
	}

	protected function setUpRecognitionLibraries() {
		$scan = scandir($this->recognitionFolder);

		$this->tagRecognitionLibrary = [];
		$this->preRecognitionLibrary = [];

		if (FALSE !== $scan) {
			for ($i = 2; $i < count($scan); $i++) {
				// ignore the first two entries of ''. and '..'
				$fullClassName = $this->recognitionNamespace . '\\' . substr(ucfirst($scan [$i]), 0,
																			 strlen($scan [$i]) - 4);
				// require_once $this->recognitionFolder . $scan [$i];
				$instance = new $fullClassName ();

				if ($instance instanceof TagRecognitionInterface) {
					$this->tagRecognitionLibrary[] = $instance;
				}

				if ($instance instanceof PreRecognitionProcessInterface) {
					$this->preRecognitionLibrary[] = $instance;
				}
			}

			// Order Recognition Library by Priority
			usort($this->tagRecognitionLibrary, function ($a, $b) {
				/* @var $a TagRecognitionInterface */
				/* @var $b TagRecognitionInterface */
				return $b->getPriority() - $a->getPriority();
			});
			usort($this->preRecognitionLibrary, function ($a, $b) {
				/* @var $a PreRecognitionProcessInterface */
				/* @var $b PreRecognitionProcessInterface */
				return $b->getPriority() - $a->getPriority();
			});
		}
	}


	/**
	 * @param string|string[] $string
	 * @return Collection
	 */
	public function recognizeTagsFromSingleString($string) {
		if (!is_array($string)) {
			$string = [
				$string
			];
		}

		$result = new Collection();
		$lib    = $this->getTagRecognitionLibrary();

		// For every string in the array $string
		foreach ($string as $tagKey => $tagValue) {
			$found    = FALSE;
			$tagValue = trim($tagValue);

			// Don't use empty tags
			if (strlen($tagValue) === 0) {
				continue;
			}

			// Run a Check over each tagrecognition to get infos
			foreach ($lib as $recognizer) {
				/* @var $recognizer AbstractTagRecognition */
				$tmp = $recognizer->extractSpecializedTag($tagValue);

				if (count($tmp) > 0) {
					$found = TRUE;

					$result = $result->merge($tmp);

					// Only continue to recognize data, if the found recognizer allows us to
					if (FALSE === $recognizer->allowOtherRecognitionsOnSuccess()) {
						break;
					}
				}
			}

			if (FALSE === $found) {
				$result->push($this->getDefaultTagFromString($tagValue));
			}
		}

		// Remove duplicates
		$result->unique();

		return $result;
	}


	/**
	 * @return TagRecognitionInterface[]
	 */
	protected function getTagRecognitionLibrary() {
		if (NULL === $this->tagRecognitionLibrary) {
			$this->setUpRecognitionLibraries();
		}

		return $this->tagRecognitionLibrary;
	}

	/**
	 * @param string $tagValue
	 * @return Tag
	 */
	protected function getDefaultTagFromString($tagValue) {
		return Keyword::firstOrCreate([
										  'title' => $tagValue
									  ]);
	}

	/**
	 * @param CompareablePropertyInterface $a
	 * @param CompareablePropertyInterface $b
	 * @return bool
	 */
	protected function tagIsEqual(CompareablePropertyInterface $a, CompareablePropertyInterface $b) {
		return $a->getCompareString() == $b->getCompareString();
	}

}

?>