<?php

namespace App\Models;

use App\Models\Traits\TimeCountTrait;
use App\Services\PreviewGeneration\Generators\AudioPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Parental\HasParent;

/**
 * Class AudioFile
 * @package App\Models
 * @property $mimeType
 */
class AudioFile extends File {
	use HasFactory;
	use HasParent;
	use TimeCountTrait;

	protected static $singleTableType = 'audio';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);
		$this->appends[] = 'mime_type';

		$this->setupTimeCountAttribute();
	}

	/**
	 * @param $size
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator($size = 'large') {
		return resolve(AudioPreviewGenerator::class);
	}

	public function getMimeTypeAttribute() {
		return $this->getLocalMimeTypeOrFallback();
	}

}
