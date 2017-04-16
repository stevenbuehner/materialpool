<?php

namespace App\Services\ResourceRecognition;

use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Resource;

class ResourceRecognitionService {

	protected $classMap;

	public function __construct() {
		$this->classMap = Keyword::getSingleTableTypeMap();
	}

	public function guessResourceFileClass($mimeType) {

		$mimeParts = preg_split('~\/~', $mimeType);

		switch ($mimeParts[0]) {
			case 'image':
				return ImageFile::class;


			default:
				return Resource::class;
		}
	}

}