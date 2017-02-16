<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use StevenBuehner\MaterialResourceStructureBundle\Entity\RessourceLimitation\ResourceLimitationInterface;

/**
 * Material
 *
 * @ORM\Table(name="material")
 * @ORM\Entity(repositoryClass="StevenBuehner\MaterialResourceStructureBundle\Repository\MaterialRepository")
 */
class Material {
	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="title", type="string", length=255)
	 */
	private $title;

	/**
	 * @var bool
	 *
	 * @ORM\Column(name="createdByBot", type="boolean")
	 */
	private $createdByBot;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="relevance", type="integer")
	 */
	private $relevance;

	/**
	 * @var bool
	 *
	 * @ORM\Column(name="deleted", type="boolean")
	 */
	private $deleted = FALSE;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="author", type="string", length=255)
	 */
	private $author = "";


	/**
	 * @var MaterialType
	 *
	 * @ORM\ManyToOne(targetEntity="MaterialType")
	 */
	private $materialType;

	/**
	 * One Material has many MaterialKeywords
	 *
	 * @var MaterialKeyword
	 * @ORM\OneToMany(targetEntity="MaterialKeyword", mappedBy="material")
	 */
	private $materialKeywords;

	/**
	 * @var ResourceLimitationInterface
	 * @ORM\Column(name="ressource_limitation", type="object", nullable=true)
	 */
	private $resourcenBegrenzung;

	/**
	 * @var Ressource
	 *
	 * @ORM\ManyToOne(targetEntity="Ressource", inversedBy="materials")
	 */
	private $resource;

	public function __construct() {
		$this->materialKeywords = new ArrayCollection();
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
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set title
	 *
	 * @param string $title
	 *
	 * @return Material
	 */
	public function setTitle($title) {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get createdByBot
	 *
	 * @return bool
	 */
	public function getCreatedByBot() {
		return $this->createdByBot;
	}

	/**
	 * Set createdByBot
	 *
	 * @param boolean $createdByBot
	 *
	 * @return Material
	 */
	public function setCreatedByBot($createdByBot) {
		$this->createdByBot = $createdByBot;

		return $this;
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
	 * @return Material
	 */
	public function setRelevance($relevance) {
		$this->relevance = $relevance;

		return $this;
	}

	/**
	 * Get deleted
	 *
	 * @return bool
	 */
	public function getDeleted() {
		return $this->deleted;
	}

	/**
	 * Set deleted
	 *
	 * @param boolean $deleted
	 *
	 * @return Material
	 */
	public function setDeleted($deleted) {
		$this->deleted = $deleted;

		return $this;
	}

	/**
	 * Get author
	 *
	 * @return string
	 */
	public function getAuthor() {
		return $this->author;
	}

	/**
	 * Set author
	 *
	 * @param string $author
	 *
	 * @return Material
	 */
	public function setAuthor($author) {
		$this->author = $author;

		return $this;
	}
}

