<?php

namespace App\Http\Video;

/**
 * Description of MediaStream
 *
 * @author Rana
 * @link http://codesamplez.com/programming/php-html5-video-streaming-tutorial
 */
abstract class AbstractMediaStream {
	protected $stream           = NULL;
	protected $mimeType         = 'video/mp4';
	protected $fileModifiedTime = 0;
	protected $fileSize         = 0;

	protected $buffer = 102400;
	protected $start  = -1;
	protected $end    = -1;

	/**
	 * Start streaming video content
	 */
	public function start() {
		$this->open();
		$this->setHeader();
		$this->stream();
		$this->end();

		exit;
	}

	/**
	 * Open stream
	 */
	protected abstract function open();

	/**
	 * Set proper header to serve the video content
	 */
	protected function setHeader() {
		ob_get_clean();

		header("Content-Type: " . $this->mimeType);
		header("Cache-Control: max-age=2592000, public");
		header("Expires: " . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
		header("Last-Modified: " . gmdate('D, d M Y H:i:s', $this->fileModifiedTime) . ' GMT');

		$this->start = 0;
		$this->end   = $this->fileSize - 1;

		header("Accept-Ranges: bytes");

		if (isset($_SERVER['HTTP_RANGE'])) {

			$c_start = $this->start;
			$c_end   = $this->end;

			list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			if (strpos($range, ',') !== FALSE) {
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $this->start-$this->end/$this->fileSize");
				exit;
			}
			if ($range == '-') {
				$c_start = $this->fileSize - substr($range, 1);
			} else {
				$range   = explode('-', $range);
				$c_start = $range[0];

				$c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
			}
			$c_end = ($c_end > $this->end) ? $this->end : $c_end;
			if ($c_start > $c_end || $c_start > $this->fileSize - 1 || $c_end >= $this->fileSize) {
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header("Content-Range: bytes $this->start-$this->end/$this->fileSize");
				exit;
			}
			$this->start = $c_start;
			$this->end   = $c_end;
			$length      = $this->end - $this->start + 1;
			fseek($this->stream, $this->start);
			header('HTTP/1.1 206 Partial Content');
			header("Content-Length: " . $length);
			header("Content-Range: bytes $this->start-$this->end/" . $this->fileSize);
		} else {
			header('HTTP/1.1 206 Partial Content');
			header("Content-Length: " . $this->fileSize);
		}

	}

	/**
	 * perform the streaming of calculated range
	 */
	protected function stream() {
		$i = $this->start;
		set_time_limit(0);
		while (!feof($this->stream) && $i <= $this->end) {
			$bytesToRead = $this->buffer;
			if (($i + $bytesToRead) > $this->end) {
				$bytesToRead = $this->end - $i + 1;
			}
			$data = fread($this->stream, $bytesToRead);
			echo $data;
			flush();
			$i += $bytesToRead;
		}
	}

	/**
	 * close currently opened stream and clean up
	 */
	protected abstract function end();
}