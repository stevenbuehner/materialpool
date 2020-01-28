<?php

namespace App\Models;

use App\Models\Traits\TimeCountTrait;
use App\Services\PreviewGeneration\Generators\AudioPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use League\Flysystem\FileNotFoundException;

class AudioFile extends File {

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
		try {
			return $this->hasLocalFile() ? $this->getLocalMimeType() : '';
		} catch (FileNotFoundException $e) {
			return '';
		}
	}

}
