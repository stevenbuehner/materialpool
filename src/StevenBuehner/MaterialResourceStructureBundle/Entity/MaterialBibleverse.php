<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use StevenBuehner\BibleVerseBundle\Entity\BibleVerse;

/**
 * MaterialKeyword
 *
 * @ORM\Table(name="material_bibleverse")
 * @ORM\Entity()
 */
class MaterialBibleverse {
	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="relevance", type="integer")
	 */
	private $relevance;

	/**
	 * Many MaterialBibleverse belong to one material
	 *
	 * @var Material
	 *
	 * @ORM\ManyToOne(targetEntity="Material", inversedBy="materialBibleVerses")
	 */
	private $material;

	/**
	 * Many MaterialKeywords belong to one Keyword
	 *
	 * @var BibleVerse
	 *
	 * @ORM\ManyToOne(targetEntity="BibleVerse", inversedBy="materialKeywords")
	 */
	private $bibleVerse;

	public function __construct() {
		$this->setRelevance(0);
	}

	/**
	 * Get id
	 *
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get relevance
	 *
	 * @return int
	 */
	public function getRelevance() {
		return $this->relevance;
	}

	/**
	 * Set relevance
	 *
	 * @param integer $relevance
	 *
	 * @return MaterialBibleverse
	 */
	public function setRelevance($relevance) {
		$this->relevance = $relevance;

		return $this;
	}

	/**
	 * @return Material
	 */
	public function getMaterial() {
		return $this->material;
	}

	/**
	 * @param mixed $material
	 * @return MaterialBibleverse
	 */
	public function setMaterial($material) {
		$this->material = $material;

		return $this;
	}

	/**
	 * @return BibleVerse
	 */
	public function getBibleVerse() {
		return $this->bibleVerse;
	}

	/**
	 * @param BibleVerse $bibleVerse
	 * @return MaterialBibleverse
	 */
	public function setBibleVerse($bibleVerse) {
		$this->bibleVerse = $bibleVerse;

		return $this;
	}

}

