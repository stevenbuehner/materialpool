<?php

namespace App\Models;

class File extends Resource {

	protected static $singleTableSubclasses = [AudioFile::class, VideoFile::class, ImageFile::class, DocumentFile::class];
	protected static $singleTableType       = 'file';
	protected static $ORIGINAL_FILENAME     = 'of';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		// Add Attribute
		$this->appends[] = 'original_filename';
	}

	public static function getValidationRules() {
		$rules         = parent::getValidationRules();
		$rules['file'] = 'required|file';

		return $rules;
	}

	public function setOriginalFileNameAttribute($originalFileName) {
		$this->setOption(self::$ORIGINAL_FILENAME, $originalFileName);
	}


	public function getOriginalFileNameAttribute() {
		return $this->getOption(self::$ORIGINAL_FILENAME, NULL);
	}

}
