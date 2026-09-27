<?php

namespace App\Services\TagExtraction\TagRecognition;

use App\Services\TagExtraction\AbstractTagRecognition;
use App\Services\TagExtraction\Interfaces\PreRecognitionProcessInterface;
use App\Services\TagExtraction\Properties\CreateDateProperty;
use App\Services\TagExtraction\Properties\Property;
use DateTime;

class Created extends AbstractTagRecognition implements PreRecognitionProcessInterface {

	const GERMAN_DATE_REGEX   = '(31|30|[012]\d|[1-9])\.(0\d|1[012]|[1-9])\.(\d{4})';
	const ENG_GERM_DATE_REGEX = '(\d{4})-(0\d|1[012]|[1-9])-(31|30|[012]\d|[1-9])';
	const POSSIBLE_PREFIX     = ['erstellt',
	                             'spoken',
	                             'created',
	                             'am',
	                             'vom',
	                             'von'];

	public function __construct() {
		$this->setPriority(50);
	}

	/**
	 * @param String $stringValue
	 * @return Property[]
	 */
	public function extractSpecializedTag($stringValue) {
		$tagValue = $this->hasPrefix(self::POSSIBLE_PREFIX, $stringValue);

		$result = [];

		if (FALSE !== $tagValue) {
			$tagValue = $this->recognizeStringFromDate($tagValue);

			if ($tagValue !== FALSE) {
				/** @var $tagValue DateTime */
				$result[] = new CreateDateProperty($tagValue); // Don't set a priority, because we don't know the source of our guessed information
			}
		}

		return $result;
	}

	/**
	 * @param string $str
	 * @return string
	 */
	private function recognizeStringFromDate($str) {
		$d = FALSE;

		// First match german date
		if (preg_match('~^' . self::GERMAN_DATE_REGEX . '$~', $str, $matches) === 1) {
			$d = new DateTime();
			$d->setDate($matches[3], $matches[2], $matches[1]);
		} else if (preg_match('~^' . self::ENG_GERM_DATE_REGEX . '$~', $str, $matches) === 1) {
			// Deutsche Verwendung des englischen Datums Y-m-d
			$d = new DateTime();
			$d->setDate($matches[1], $matches[2], $matches[3]);
		}

		if ($d instanceof DateTime && $this->isValidDate($d) === FALSE) {
			$d = FALSE;
		}

		return $d;
	}

	protected function isValidDate(DateTime $dateTime) {

		$year = $dateTime->format('Y');

		// Max values for timestamp in MySql
		if ($year < 1970 || $year >= 2038) {
			return FALSE;
		}

		return TRUE;
	}

	public function allowOtherRecognitionsOnSuccess() {
		return FALSE;
	}

	/**
	 * Replace "am DATUM" with "erstellt: DATUM" (damit es nicht als Bibelstelle "amos ..." erkennt wird
	 *
	 * @param string $value
	 * @param array $context
	 * @return string
	 */
	public function preProcessInput($inputValue, $context) {
		$inputValue = $inputValue ?? '';

		$resultString = preg_replace('~(^|(?![0-9]))(am)(: ?| )(' . self::GERMAN_DATE_REGEX . '|' . self::ENG_GERM_DATE_REGEX . ')~i',
			'erstellt: $4', $inputValue);

		return [$resultString, $tags = []];
	}

	/**
	 * Returns false if no recogntiion should take place on this string and true if to continue normally
	 *
	 * @return bool
	 */
	public function allowTagRecognitionAfterThis() {
		return TRUE;
	}
}

?>
