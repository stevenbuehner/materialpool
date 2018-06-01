<?php

namespace App\Models;

use App\Services\PreviewGeneration\Generators\TextLargePreviewGenerator;
use App\Services\PreviewGeneration\Generators\TextThumbPreviewGenerator;
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
	protected static $FIRST_LINE      = 'fl';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		// Add Attribute
		$this->appends[]  = 'content';
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
	 * @param $size
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator($size = 'large') {

		switch ($size) {
			case 'thumb':
				return resolve(TextThumbPreviewGenerator::class);
			default:
				return resolve(TextLargePreviewGenerator::class);
		}
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
}
