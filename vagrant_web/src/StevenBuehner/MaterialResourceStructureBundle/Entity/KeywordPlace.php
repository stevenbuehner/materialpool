<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class KeywordPerson
 *
 * @package StevenBuehner\MaterialResourceStructureBundle\Entity
 * @ORM\Entity()
 */
class KeywordPlace extends \StevenBuehner\MaterialResourceStructureBundle\Entity\Keyword {

	const LONGITUDE = 'long';
	const LATITUDE  = 'lat';


	public function __construct() {
		parent::__construct();
		$this->extraData[self::LONGITUDE] = NULL;
		$this->extraData[self::LATITUDE]  = NULL;
	}

	/**
	 * @return float|null
	 */
	public function getLongitude() {
		return $this->extraData[self::LONGITUDE];
	}

	/**
	 * @param float|null $longitude
	 */
	public function setLongitude($longitude) {
		$this->extraData[self::LONGITUDE] = $longitude;
	}

	/**
	 * @return float|null
	 */
	public function getLatitude() {
		return $this->extraData[self::LATITUDE];
	}

	/**
	 * @param float|null $latitude
	 */
	public function setLatitude($latitude) {
		$this->extraData[self::LATITUDE] = $latitude;
	}
}