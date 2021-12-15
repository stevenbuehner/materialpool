<?php

namespace App\Models;

use App\Services\PreviewGeneration\Generators\ImagePreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImageFile extends File {
	use HasFactory;

	protected static $singleTableType = 'image';

	public static function getValidationRules() {
		$rules         = parent::getValidationRules();
		$rules['file'] = 'bail|required|file|image';

		return $rules;
	}

	/**
	 * @param $size
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator($size = 'large') {
		return resolve(ImagePreviewGenerator::class);
	}
}
