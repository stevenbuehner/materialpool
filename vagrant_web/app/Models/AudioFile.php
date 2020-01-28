<?php

namespace App\Models;

use App\Models\Traits\TimeCountTrait;
use App\Services\PreviewGeneration\Generators\AudioPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;

class AudioFile extends File {

	use TimeCountTrait;

	protected static $singleTableType = 'audio';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		$this->setupTimeCountAttribute();
	}

	/**
	 * @param $size
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator($size = 'large') {
		return resolve(AudioPreviewGenerator::class);
	}

}
