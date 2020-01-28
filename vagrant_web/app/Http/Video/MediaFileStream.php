<?php

namespace App\Http\Video;

class MediaFileStream extends AbstractMediaStream {
	protected $path;

	function __construct($filePath) {
		$this->path             = $filePath;
		$this->fileModifiedTime = @filemtime($filePath);
		$this->fileSize         = filesize($filePath);
	}

	/**
	 * Open stream
	 */
	protected function open() {
		if (!($this->stream = fopen($this->path, 'rb'))) {
			die('Could not open stream for reading');
		}
	}

	/**
	 * close curretly opened stream
	 */
	protected function end() {
		if (is_resource($this->stream)) {
			fclose($this->stream);
		}
	}
}