<?php

namespace App\Services\ResourceRecognition;

use App\Models\AudioFile;
use App\Models\DocumentFile;
use App\Models\File;
use App\Models\ImageFile;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
use App\Models\Url;
use App\Models\VideoFile;
use Illuminate\Http\UploadedFile;

class ResourceRecognitionService {


	public function __construct() {
	}

	public function guessResourceFile(UploadedFile $requestFile) {

		$mimeType  = $requestFile->getClientMimeType();
		$mimeParts = preg_split('~\/~', $mimeType);

		// Check for length (too big files are stored as file and not in DB)
		if ($mimeType == 'text/plain' && $requestFile->getSize() < 1024 * 512 /* 0,5 MB */) {
			return Text::class;
		}

		return $this->guessResourceFileFromMimeType($mimeType);
	}

	/**
	 * @param $mimeType
	 * @return string
	 */
	public function guessResourceFileFromMimeType($mimeType) {

		$class = Resource::class;

		switch ($mimeType) {
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
			case 'image/tiff':

				$class = ImageFile::class;
				break;

			case 'text/plain':
				// Check for first line (if it has keywords etc. than use it as so
				break;

			case'application/pdf':

				$class = PdfFile::class;
				break;

			case'application/msword':
			case'application/vnd.openxmlformats-officedocument.wordprocessingml.document':

				$class = DocumentFile::class;
				break;

			case 'video/mp4':
			case 'video/quicktime':

				$class = VideoFile::class;
				break;

			case 'audio':
			case 'audio/mp3':

				$class = AudioFile::class;
				break;

			case 'text/rtf':


			default:

				$class = File::class;
				break;
		}

		return $class;
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public function guessResourceContent(&$content) {

		if (preg_match('%^((https?://)|(www\.))([a-z0-9-].?)+(:[0-9]+)?(/.*)?$%i', $content) === 1) {
			return Url::class;
		}

		return Text::class;
	}

}