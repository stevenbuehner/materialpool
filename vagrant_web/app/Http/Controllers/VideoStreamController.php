<?php

namespace App\Http\Controllers;

use App\Http\Video\VideoStream;
use App\Models\VideoFile;

class VideoStreamController extends Controller {

	public function __construct() {
		$this->middleware(['auth']);
	}

	public function stream(VideoFile $resource) {

		if (!$resource->hasLocalFile()) {
			return response('File is not local - Streaming impossible', 501);
		}

		$stream = $resource->getLocalFileStream();

		if ($stream !== FALSE) {
			$videoStream = new VideoStream($stream, $resource->getLocalSize(), $resource->getLocalLastModified());
			$videoStream->start();
		}


	}

}
