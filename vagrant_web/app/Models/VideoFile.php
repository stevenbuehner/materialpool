<?php

namespace App\Models;

use App\Services\PreviewGeneration\Generators\VideoPreviewGenerator;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;

class VideoFile extends File {

	protected static $singleTableType = 'video';

	/**
	 * @return PreviewGeneratorInterface
	 */
	public function getPreviewGenerator() {
		return resolve(VideoPreviewGenerator::class);
	}
}
