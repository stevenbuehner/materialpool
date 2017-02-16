<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RessourceImage
 *
 * @package StevenBuehner\MaterialResourceStructureBundle\Entity
 * @ORM\Entity()
 */
class RessourceImage extends Ressource {

	const DIMENSION_X_KEY = "dimX";
	const DIMENSION_Y_KEY = "dimY";

	/**
	 * RessourceImage constructor.
	 *
	 * @param string $url
	 */
	public function __construct() {
		parent::__construct();
		$this->setDimensionX(NULL);
		$this->setDimensionY(NULL);
	}


	/**
	 * @param int $dimension
	 */
	public function setDimensionX($dimension) {
		$this->extraData[self::DIMENSION_X_KEY] = $dimension;
	}

	/**
	 * @param int $dimension
	 */
	public function setDimensionY($dimension) {
		$this->extraData[self::DIMENSION_Y_KEY] = $dimension;
	}

	/**
	 * @return int|NULL
	 */
	public function getDimensionX() {
		return $this->extraData[self::DIMENSION_X_KEY];
	}

	/**
	 * @return int|NULL
	 */
	public function getDimensionY() {
		return $this->extraData[self::DIMENSION_Y_KEY];
	}


}