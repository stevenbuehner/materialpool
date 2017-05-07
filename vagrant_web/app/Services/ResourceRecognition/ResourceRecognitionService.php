<?php

namespace App\Services\ResourceRecognition;

use App\Models\AudioFile;
use App\Models\DocumentFile;
use App\Models\File;
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Resource;
use App\Models\VideoFile;

class ResourceRecognitionService {

	protected $classMap;

	public function __construct() {
		$this->classMap = Keyword::getSingleTableTypeMap();
	}

	public function guessResourceFileClass($mimeType) {

		$mimeParts  = preg_split('~\/~', $mimeType);
		$typeGroup  = $mimeParts[0];
		$typeDetail = $mimeParts[1];

		switch ($mimeType) {
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
				return ImageFile::class;

			case'application/pdf':
			case'application/msword':
			case'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
			case'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
				return DocumentFile::class;

			case 'video':
				return VideoFile::class;

			case 'audio':
				return AudioFile::class;

			case 'text/rtf':
			default:

				return File::class;
		}
	}

}