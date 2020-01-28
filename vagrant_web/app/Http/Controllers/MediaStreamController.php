<?php

namespace App\Http\Controllers;

use App\Http\Video\MediaStream;
use App\Models\AudioFile;
use App\Models\File;
use App\Models\VideoFile;

class MediaStreamController extends Controller {

	public function __construct() {
		$this->middleware(['auth']);
	}

	public function stream(File $resource) {

		if (!$resource instanceof VideoFile && !$resource instanceof AudioFile) {
			return response('Resource is neither Audio- nor VideoFile', 501);
		}

		if (!$resource->hasLocalFile()) {
			return response('File is not local - Streaming impossible', 501);
		}

		$stream = $resource->getLocalFileStream();

		if ($stream !== FALSE) {
			$videoStream = new MediaStream($stream, $resource->getLocalSize(), $resource->getLocalLastModified());
			$videoStream->start();
		}


	}

}
