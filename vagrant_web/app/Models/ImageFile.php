<?php

namespace App\Models;

use App\Services\PreviewGeneration\Generators\ImagePreviewGenerator;

class ImageFile extends File {

	protected static $singleTableType = 'image';

	public static function getValidationRules() {
		$rules         = parent::getValidationRules();
		$rules['file'] = 'bail|required|file|image';

		return $rules;
	}

	public function getPreviewGenerator() {
		return resolve(ImagePreviewGenerator::class);
	}
}
