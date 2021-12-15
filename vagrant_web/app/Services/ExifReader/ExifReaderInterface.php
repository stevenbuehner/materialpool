<?php

namespace App\Services\ExifReader;

use PHPExif\Exif;

interface ExifReaderInterface {

	/**
	 * @param $file
	 * @return Exif
	 */
	public function read($file);

}
