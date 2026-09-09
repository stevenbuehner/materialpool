<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Parental\HasParent;
use App\Models\Traits\PageCountTrait;
use App\Services\PreviewGeneration\Generators\PdfPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;

/**
 * Class PdfFile
 *
 * @package App\Models
 *
 */
class PdfFile extends File {
	use HasFactory;
	use HasParent;

	use PageCountTrait;

	protected static $singleTableType = 'pdf';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		$this->setupPageCountAttribute();
	}

	public static function getValidationRules() {
		$rules         = parent::getValidationRules();
		$rules['file'] = 'required|file|mimes:pdf';

		return $rules;
	}

	/**
	 * @param $size
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator($size = 'large') {
		return resolve(PdfPreviewGenerator::class);
	}


}
