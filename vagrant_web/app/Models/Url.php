<?php

namespace App\Models;

use App\Services\TagExtraction\ResourceHandles\TextContentInterface;

class Url extends Resource implements TextContentInterface {

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
	 * @return string
	 */
	public function getContent() {
		return $this->getUrlAttribute();
	}

	public function getUrlAttribute() {
		return $this->getOption(self::$URL_OPTION);
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
}
