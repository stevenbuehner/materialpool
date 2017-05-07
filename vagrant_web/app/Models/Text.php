<?php

namespace App\Models;

use App\Services\TagExtraction\ResourceHandles\HandlerInterface;
use App\Services\TagExtraction\ResourceHandles\TextContentHandler;
use App\Services\TagExtraction\ResourceHandles\TextContentInterface;

/**
 * Class Text
 *
 * @package App\Models
 * @property string $content
 */
class Text extends Resource implements TextContentInterface {

	protected static $singleTableType = 'text';
	protected static $CONTENT_OPTION  = 'c';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		// Add Attribute
		// $this->appends[]  = 'content';
		$this->fillable[] = 'content';
		// TODO: $this->additionalEditViews[] = 'resources.text.edit-partial';
	}

	public static function getValidationRules() {
		$rules            = parent::getValidationRules();
		$rules['content'] = 'string|min:3';

		return $rules;
	}

	/**
	 * @return HandlerInterface[]
	 */
	public function getTagExtractionClasses() {
		return [
			TextContentHandler::class
		];
	}

	public function getContent() {
		return $this->getOption(self::$CONTENT_OPTION);
	}

	public function getContentAttribute() {
		return $this->getOption(self::$CONTENT_OPTION);
	}

	public function setContentAttribute($value) {
		$value = trim($value);
		$this->setOption(self::$CONTENT_OPTION, $value);
		$this->content_hash = sha1($value);
	}
}
