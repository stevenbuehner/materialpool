<?php

namespace App\Services\ExifReader;

use PHPExif\Mapper\Exiftool;

class ExifData extends Exiftool {

	public function mapRawData(array $data) {
		return parent::mapRawData($data);
	}
}