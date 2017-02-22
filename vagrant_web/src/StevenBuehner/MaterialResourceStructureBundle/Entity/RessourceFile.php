<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RessourceFile
 *
 * @package StevenBuehner\MaterialResourceStructureBundle\Entity
 * @ORM\Entity()
 */
class RessourceFile extends Ressource {


	/**
	 * RessourceFile constructor.
	 *
	 * @param string $url
	 */
	public function __construct($url) {
		parent::__construct();
	}


}