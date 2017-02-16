<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RessourceText
 *
 * @package StevenBuehner\MaterialResourceStructureBundle\Entity
 * @ORM\Entity()
 */
class RessourceText extends Ressource {


	/**
	 * RessourceText constructor.
	 *
	 * @param string $text
	 */
	public function __construct($text) {
		parent::__construct();
	}

	/**
	 * @param string $text
	 */
	public function setText($text) {
		$this->setPath($text);
	}

	/**
	 * @return string
	 */
	public function getText() {
		return $this->getPath();
	}


}