<?php

namespace App\Services\TagExtraction;

use App\Models\Keyword;
use App\Services\TagExtraction\Interfaces\PropertyInterface;
use App\Services\TagExtraction\Interfaces\TagRecognitionInterface;

abstract class AbstractTagRecognition implements TagRecognitionInterface {
	/**
	 * Integer Value between 0 and 100
	 * The highest values are always rendered first
	 *
	 * @var int
	 */
	protected $priority = 0;

	/**
	 * If any special Tags are found, than they are returned
	 * They inherit from OCA\KnowledgeBase\Model\Tags\Tag
	 *
	 * @param String $stringValue
	 * @return Keyword[]|PropertyInterface[]
	 */
	abstract public function extractSpecializedTag($stringValue);

	/**
	 * This function can be called to find out if other recognition classes should be run, although this one has found
	 * already some Tags
	 *
	 * @return boolean
	 */
	abstract public function allowOtherRecognitionsOnSuccess();

	public function getPriority() {
		return $this->priority;
	}

	public function setPriority($prio) {
		$this->priority = ( int ) $prio;
	}

	/**
	 * Searches a string for one or multiple prefixes
	 * Retuns only the suffix (without prefix) if found.
	 * Otherwise it returns false
	 * Example: ('von', "von: Steven") would return "Steven"
	 *
	 * @param string|array $prefix
	 * @param string       $haystack
	 * @return string|false
	 */
	protected function hasPrefix($prefix, $haystack) {
		if (!is_array($prefix)) {
			$prefix = [
				$prefix
			];
		}

		$pregSearchString = '~^ *(' . join('|', $prefix) . ')(: *| +)(.*?) *$~i';

		if (1 === preg_match($pregSearchString, $haystack, $match)) {
			return $match [3];
		}

		// Nothing was found with this prefix
		return FALSE;
	}

}

?>