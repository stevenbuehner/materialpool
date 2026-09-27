<?php

namespace App\Services\ExifReader;

use DateTime;
use PHPExif\Exif;
use PHPExif\Mapper\Exiftool;

class ExifMapper extends Exiftool {

	const BIBLEVERSES = 'bibleverses';

	protected $data;

	public function mapRawData(array $rawData) {

		$this->data = parent::mapRawData($rawData);

		$this->getFirstFoundValue(['XMP-dc:Title', 'title', 'Title', 'XMP-dc:Subject', 'IPTC:ObjectName'], $rawData, Exif::TITLE, FALSE);
		$this->getFirstFoundValue(['Comments', 'comments', self::CAPTION], $rawData, Exif::CAPTION, FALSE);
		$this->getFirstFoundValue(['PDF:Author', 'Author', 'XMP-dc:Creator', 'By-line', self::ARTIST], $rawData, Exif::AUTHOR, FALSE);

		$dateString                      = $this->getFirstFoundValue(['XML:CreateDate', 'PDF:CreateDate', self::DATETIMEORIGINAL, 'System:FileInodeChangeDate', 'System:FileModifyDate'], $rawData, FALSE, FALSE);
		$this->data[EXIF::CREATION_DATE] = new DateTime($dateString);

		$this->prioritiseKeywords(['XML:Keywords', 'XML:Category', 'AppleKeywords', 'XML:Bibelstelle'], $rawData, Exif::KEYWORDS);
		// $this->prioritiseKeywords(['XML:Bibelstelle'], $rawData, self::BIBLEVERSES);

		return $this->data;

	}

	protected function getFirstFoundValue($keys, &$data, $storageKey, $default = FALSE) {

		$result = $default;

		foreach ($keys as $key) {
			if (array_key_exists($key, $data)) {
				$result = $data[$key];
				break;
			}
		}

		if ($result !== FALSE && $storageKey !== FALSE) {
			$this->data[$storageKey] = $result;
		}

		return $result;

	}

	protected function prioritiseKeywords($keys, &$data, $storageKey) {

		$keywordList = $this->getAllFoundValues($keys, $data, '');
		$joined      = join(', ', $keywordList);

		if (strlen($joined) > 0) {
			$this->data[$storageKey] = $joined;
		}

		return $joined;

	}

	protected function getAllFoundValues($keys, &$data, $storageKey) {

		$response = [];

		foreach ($keys as $key) {
			if (array_key_exists($key, $data)) {
				$response[] = $data[$key];
			}
		}

		$this->data[$storageKey] = $response;

		return $response;

	}

}