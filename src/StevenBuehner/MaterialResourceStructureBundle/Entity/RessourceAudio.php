<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RessourceAudio
 *
 * @package StevenBuehner\MaterialResourceStructureBundle\Entity
 * @ORM\Entity()
 */
class RessourceAudio extends RessourceMedia {

	/**
	 * RessourceAudio constructor.
	 *
	 * @param string $url
	 */
	public function __construct() {
		parent::__construct();
	}


}