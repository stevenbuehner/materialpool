<?php

namespace App\Services\TagExtraction\TagRecognition;

use App\Services\TagExtraction\Interfaces\PreRecognitionProcessInterface;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use App\Services\TagExtraction\Properties\TitleProperty;

class Title implements PreRecognitionProcessInterface {

	const RECOGNIZED_LABELS = ['title', 'titel'];

	public function __construct() {
	}

	/**
	 * PreProcess found Title. Because a title might contain a bibleverse.
	 *
	 * @param string $value
	 * @param array $context
	 * @return array
	 */
	public function preProcessInput($inputValue, $context) {
		$pregSearchString = '~(^|,|;)\s*(' . join('|', self::RECOGNIZED_LABELS) . '):?\s+([^,;]*?)\s*(?=$|,|;)~i';
		$tags             = [];

		if (1 === preg_match($pregSearchString, $inputValue, $match)) {
			$titleProp = new TitleProperty($match[3]);
			$titleProp->setRelevance(RelevanceInterface::RELEVANCE_USER_MAX);
			$tags[] = $titleProp;

			$inputValue = str_replace($match[0], '', $inputValue);
		}

		return [$inputValue, $tags];
	}

	/**
	 * Returns false if no recogntion should take place on this whole (!) result-string and true if to continue normally
	 *
	 * @return bool
	 */
	public function allowTagRecognitionAfterThis() {
		return TRUE;
	}

	/**
	 * Integer Value between 0 and 100
	 * The highest values are always rendered first
	 *
	 * @return int
	 */
	public function getPriority() {
		return 20;
	}
}

?>