<?php

namespace App\Services\TagExtraction\TagRecognition;

use App\Models\Language as LanguageModel;
use App\Services\TagExtraction\AbstractTagRecognition;
use App\Services\TagExtraction\Properties\KeywordProperty;

class Language extends AbstractTagRecognition {


	const LANG_NO_LANG = NULL;

	public function __construct() {
		$this->setPriority(40);
	}

	/**
	 * @param String $stringValue
	 * @return LanguageModel[]
	 */
	public function extractSpecializedTag($stringValue) {
		$tagValue = $this->hasPrefix([
										 'language',
										 'lang',
										 'sprache'
									 ], $stringValue);

		$result = [];


		if (FALSE !== $tagValue) {

			$langCode = $this->getLanguageCodeFromString($tagValue);

			if ($langCode !== self::LANG_NO_LANG) {
				$result[] = new KeywordProperty($langCode,
												LanguageModel::class); // Don't set a priority, because we don't know the source of our guessed information
			}

		} else {

			// Search the whole keyword
			$found = $this->getLanguageCodeFromString($stringValue);
			if ($found !== self::LANG_NO_LANG) {
				$result[] = new KeywordProperty($stringValue,
												LanguageModel::class); // Don't set a priority, because we don't know the source of our guessed information
			}

		}

		return $result;
	}

	/**
	 * @param string $value
	 * @return null|string
	 */
	protected function getLanguageCodeFromString($value) {
		$result = LanguageModel::getLanguageCodeForWritten($value);

		if ($result === NULL) {
			$result = self::LANG_NO_LANG;
		}

		return $result;
	}

	public function allowOtherRecognitionsOnSuccess() {
		return FALSE;
	}

}

?>