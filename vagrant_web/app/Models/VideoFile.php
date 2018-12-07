<?php

namespace App\Models;

use App\Services\PreviewGeneration\Generators\VideoPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;

class VideoFile extends File {

	protected static $singleTableType = 'video';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);
		$this->appends[]  = 'mime_type';
	}

	/**
	 * @param $size
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator($size = 'large') {
		return resolve(VideoPreviewGenerator::class);
	}

	public function getMimeTypeAttribute() {
		return $this->hasLocalFile() ? $this->getLocalMimeType() : '';
	}
}
