<?php

namespace App\Services\ResourceRecognition;

use App\Models\AudioFile;
use App\Models\DocumentFile;
use App\Models\File;
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
use App\Models\VideoFile;
use Illuminate\Http\UploadedFile;

class ResourceRecognitionService {

	protected $classMap;

	public function __construct() {
		$this->classMap = Keyword::getSingleTableTypeMap();
	}

	public function guessResourceFile(UploadedFile $requestFile) {

		$mimeType  = $requestFile->getMimeType();
		$mimeParts = preg_split('~\/~', $mimeType);
		$class     = Resource::class;

		switch ($mimeType) {
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':

				$class = ImageFile::class;
				break;

			case 'text/plain':
				// Check for length (too big files are stored as file and not in DB)
				if ($requestFile->getSize() < 1024 * 512 /* 0,5 MB */) {
					$class = Text::class;
				}

				// Check for first line (if it has keywords etc. than use it as so
				break;

			case'application/pdf':

				$class = PdfFile::class;
				break;

			case'application/msword':
			case'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
			case'application/vnd.openxmlformats-officedocument.wordprocessingml.document':

				$class = DocumentFile::class;
				break;

			case 'video/mp4':

				$class = VideoFile::class;
				break;

			case 'audio':

				$class = AudioFile::class;
				break;

			case 'text/rtf':


			default:

				$class = File::class;
				break;
		}

		return $class;
	}

}