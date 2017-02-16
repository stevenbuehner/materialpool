<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class RessourceDocument
 *
 * @package StevenBuehner\MaterialResourceStructureBundle\Entity
 * @ORM\Entity()
 */
class RessourceDocument extends Ressource {

	const PAGE_COUNT_KEY = "pages";

	/**
	 * RessourceDocument constructor.
	 *
	 * @param string $url
	 */
	public function __construct() {
		parent::__construct();
		$this->setPageCount(NULL);
	}

	/**
	 * @param int $pageCount
	 */
	public function setPageCount($pageCount) {
		$this->extraData[self::PAGE_COUNT_KEY] = $pageCount;
	}

	/**
	 * @return int|NULL
	 */
	public function getPageCount() {
		return $this->extraData[self::PAGE_COUNT_KEY];
	}

}