<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MaterialKeyword
 *
 * @ORM\Table(name="material_keyword")
 * @ORM\Entity(repositoryClass="StevenBuehner\MaterialResourceStructureBundle\Repository\MaterialKeywordRepository")
 */
class MaterialKeyword {
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
	 * Many MaterialKeywords belong to one product
	 *
	 * @var Material
	 *
	 * @ORM\ManyToOne(targetEntity="Material", inversedBy="materialKeywords")
	 */
	private $material;

	/**
	 * Many MaterialKeywords belong to one Keyword
	 *
	 * @var Keyword
	 *
	 * @ORM\ManyToOne(targetEntity="Keyword", inversedBy="materialKeywords")
	 */
	private $keyword;

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
	 * @return MaterialKeyword
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
	 */
	public function setMaterial($material) {
		$this->material = $material;

		return $this;
	}

	/**
	 * @return Keyword
	 */
	public function getKeyword() {
		return $this->keyword;
	}

	/**
	 * @param Keyword $keyword
	 */
	public function setKeyword($keyword) {
		$this->keyword = $keyword;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getKeywordTitle() {
		return $this->keyword->getTitle();
	}

	/**
	 * @return string
	 */
	public function getMaterialTitle() {
		return $this->material->getTitle();
	}
}

