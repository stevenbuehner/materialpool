<?php

namespace App\Models;

use App\Services\PreviewGeneration\Generators\VideoPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;

class VideoFile extends File {

	protected static $singleTableType = 'video';

	/**
	 * @param $size
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator($size = 'large') {
		return resolve(VideoPreviewGenerator::class);
	}
}
