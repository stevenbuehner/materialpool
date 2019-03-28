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
use Illuminate\Support\Facades\Log;

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
			case 'text/markdown':
				// Check for first line (if it has keywords etc. than use it as so
				break;

			case'application/pdf':

				$class = PdfFile::class;
				break;

			case'application/msword':
			case'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
			case 'application/vnd.oasis.opendocument.presentation':
			case 'application/vnd.oasis.opendocument.spreadsheet':
			case 'application/vnd.oasis.opendocument.text':
			case 'application/vnd.ms-powerpoint':
			case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
			case 'application/vnd.ms-excel':
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':

				$class = DocumentFile::class;
				break;

			case 'video/mp4':
			case 'video/mpeg':
			case 'video/quicktime':
			case 'video/ogg':
			case 'video/3gpp':

				$class = VideoFile::class;
				break;

			case 'audio':
			case 'audio/mp3':
			case 'audio/mpeg':
			case 'audio/aac':
			case 'audio/ogg':
			case 'audio/3gpp':

				$class = AudioFile::class;
				break;

			case 'text/rtf':


			default:

				Log::warning('Unbekannter MIME-Type: ' . $mimeType);
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