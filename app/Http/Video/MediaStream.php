<?php

namespace App\Http\Video;

use Exception;

class MediaStream extends AbstractMediaStream {
	protected $path;

	/**
	 * TODO: Not tested or used so far .... (Steven)
	 * MediaStream constructor.
	 *
	 * @param $stream
	 * @param $filesize
	 * @param $lastModified
	 * @throws Exception
	 */
	function __construct($stream, $filesize, $lastModified, $mimeType) {
		if (!is_resource($stream)) {
			throw new Exception('Invalid Stream-Resource');
		}

		$this->stream           = $stream;
		$this->fileSize         = $filesize;
		$this->fileModifiedTime = $lastModified;
		$this->mimeType         = $mimeType;
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