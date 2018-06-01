<?php

namespace App\Models;

use App\Services\Processors\ContentHashProviderInterface;
use App\Services\TagExtraction\ResourceHandles\TextContentInterface;

class Url extends Resource implements TextContentInterface, ContentHashProviderInterface {

	protected static $singleTableType = 'link';
	protected static $URL_OPTION      = 'u';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		// Add Attribute
		// $this->appends[]  = 'url';
		$this->appends[]  = 'content';
		$this->fillable[] = 'url';
	}

	public static function getValidationRules() {
		$rules        = parent::getValidationRules();
		$rules['url'] = 'string|min:4|url';

		return $rules;
	}

	/**
	 * @param string $content
	 */
	public function setContent($value) {
		$this->setUrlAttribute($value);
	}

	public function setUrlAttribute($url) {
		$value = trim($url);
		$this->setOption(self::$URL_OPTION, $value);
		$this->content_hash = sha1($value);
	}

	public function getContentAttribute() {
		return $this->getUrlAttribute();
	}

	public function getUrlAttribute() {
		return $this->getOption(self::$URL_OPTION);
	}

	/**
	 * Stores the first line, if it is needed in the future
	 *
	 * @param string $firstLine
	 * @return
	 */
	public function setFirstLine($firstLine) {
		$this->setOption(self::$FIRST_LINE, $firstLine);
	}

	/**
	 * Returns the previously stored firstLine or returns FALSE if none has been stored yet
	 *
	 * @return string|FALSE
	 */
	public function getFirstLine() {
		if ($this->hasOption(self::$FIRST_LINE)) {
			return $this->getOption(self::$FIRST_LINE);
		} else {
			return FALSE;
		}
	}

	/**
	 * @return string
	 */
	public function getContentsForHash() {
		return $this->getContent();
	}

	/**
	 * @return string
	 */
	public function getContent() {
		return $this->getUrlAttribute();
	}
}
