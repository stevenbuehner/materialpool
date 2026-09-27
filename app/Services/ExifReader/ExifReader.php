<?php

namespace App\Services\ExifReader;

use PHPExif\Reader\Reader;

class ExifReader extends Reader implements ExifReaderInterface {

	public function read($file) {
		// TODO: Implement read() method.
		return parent::read($file);

	}

}