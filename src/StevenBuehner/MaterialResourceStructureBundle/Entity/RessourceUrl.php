<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RessourceUrl
 *
 * @package StevenBuehner\MaterialResourceStructureBundle\Entity
 * @ORM\Entity()
 */
class RessourceUrl extends Ressource {

	const URL_KEY = 'url';

	/**
	 * RessourceUrl constructor.
	 *
	 * @param string $url
	 */
	public function __construct($url) {
		parent::__construct();
		$this->setUrl($url);
	}

	/**
	 * @param string $url
	 */
	public function setUrl($url) {
		$this->extraData[self::URL_KEY] = $url;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->extraData[self::URL_KEY];
	}


}