<?php

namespace App\Models;

use App\Models\Traits\TimeCountTrait;
use App\Services\PreviewGeneration\Generators\VideoPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Parental\HasParent;

/**
 * Class VideoFile
 * @package App\Models
 * @property $mimeType
 */
class VideoFile extends File {
	use HasFactory;
	use HasParent;
	use TimeCountTrait;

	protected static $singleTableType = 'video';

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
		return resolve(VideoPreviewGenerator::class);
	}

	public function getMimeTypeAttribute() {
		try {
			return $this->hasLocalFile() ? $this->getLocalMimeType() : '';
		} catch (FileNotFoundException $e) {
			return '';
		}
	}
}
