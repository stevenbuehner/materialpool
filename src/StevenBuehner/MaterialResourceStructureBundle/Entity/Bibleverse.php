<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * MaterialKeyword
 *
 * @ORM\Table(name="bibleverse")
 * @ORM\Entity()
 *
 */
class Bibleverse extends \StevenBuehner\BibleVerseBundle\Entity\BibleVerse {

	/**
	 * One BibleVerse has many MaterialBibleverses
	 *
	 * @var MaterialBibleverse
	 * @ORM\OneToMany(targetEntity="MaterialBibleverse", mappedBy="bibleVerse")
	 */
	private $materialKeywords;

	public function __construct() {
		parent::__construct();
		$this->materialKeywords = new ArrayCollection();
	}

	/**
	 * @return MaterialBibleverse
	 */
	public function getMaterialKeywords() {
		return $this->materialKeywords;
	}

	/**
	 * @param MaterialBibleverse $materialKeywords
	 */
	public function setMaterialKeywords($materialKeywords) {
		$this->materialKeywords = $materialKeywords;
	}

}

