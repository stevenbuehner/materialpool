<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RessourceMedia
 *
 * @package StevenBuehner\MaterialResourceStructureBundle\Entity
 * @ORM\Entity()
 */
abstract class RessourceMedia extends Ressource {

	const DIMENSION_KEY = "dur";

	/**
	 * RessourceMedia constructor.
	 *
	 * @param string $url
	 */
	public function __construct() {
		parent::__construct();
		$this->setDuration(NULL);
	}

	/**
	 * @param float $duration
	 */
	public function setDuration($duration) {
		$this->extraData[self::DIMENSION_KEY] = $duration;
	}

	/**
	 * @return float|NULL
	 */
	public function getDuration() {
		return $this->extraData[self::DIMENSION_KEY];
	}

}