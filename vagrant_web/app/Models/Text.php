<?php

namespace App\Models;

use App\Services\PreviewGeneration\Generators\TextPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
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

	/**
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator() {
		return resolve(TextPreviewGenerator::class);
	}

	/**
	 * @param string $content
	 */
	public function setContent($content) {
		$this->setContentAttribute($content);
	}

	public function setContentAttribute($value) {
		$value = trim($value);
		$this->setOption(self::$CONTENT_OPTION, $value);
		$this->content_hash = sha1($value);
	}
}
