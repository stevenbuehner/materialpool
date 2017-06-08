<?php

namespace App\Http\Video;

class VideoStream extends AbstractVideoStream {
	protected $path;

	function __construct($stream, $filesize, $lastModified) {
		if (!is_resource($stream)) {
			throw new \Exception('Invalid Stream-Resource');
		}

		$this->stream           = $stream;
		$this->fileSize         = $filesize;
		$this->fileModifiedTime = $lastModified;
	}

	/**
	 * Open stream
	 */
	protected function open() {
		// Stream is opened already -> nothing to do

		if (!is_resource($this->stream)) {
			die('Resource is not open anymore');
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