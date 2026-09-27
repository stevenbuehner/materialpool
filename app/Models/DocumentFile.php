<?php

namespace App\Models;

use App\Models\Traits\PageCountTrait;
use App\Services\PreviewGeneration\Generators\DocumentPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Parental\HasParent;

class DocumentFile extends File {
	use HasFactory;
	use HasParent;

	use PageCountTrait;

	protected static $singleTableType = 'doc';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		$this->setupPageCountAttribute();
	}

	/**
	 * @return array
	 */
	public static function getValidationRules() {
		$rules         = parent::getValidationRules();
		$rules['file'] = 'required|file|mimes:doc,docx,xls,xlsx,ppt,pptx';

		return $rules;
	}

	/**
	 * @param string $size
	 * @return PreviewGeneratorInterface|mixed
	 */
	public function getPreviewGenerator($size = 'large') {
		return resolve(DocumentPreviewGenerator::class);
	}

}
