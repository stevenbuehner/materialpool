<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Keyword
 *
 * @ORM\Table(name="keyword")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="className", type="string")
 * @ORM\DiscriminatorMap({"keyword" = "Keyword", "person" = "KeywordPerson", "place" = "KeywordPlace"})
 * @ORM\Entity(repositoryClass="StevenBuehner\MaterialResourceStructureBundle\Repository\KeywordRepository")
 */
class Keyword {
	/**
	 * @var string
	 *
	 * @ORM\Column(name="title", type="string", length=255)
	 */
	protected $title;
	/**
	 * @var int
	 *
	 * @ORM\Column(name="leftParent", type="integer")
	 */
	protected $leftParent;
	/**
	 * @var int
	 *
	 * @ORM\Column(name="rightParent", type="integer")
	 */
	protected $rightParent;
	/**
	 * @var array
	 *
	 * @ORM\Column(name="extra_data", type="array")
	 */
	protected $extraData = [];
	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;
	/**
	 * One Material has many MaterialKeywords
	 *
	 * @var MaterialKeyword
	 * @ORM\OneToMany(targetEntity="MaterialKeyword", mappedBy="keyword")
	 */
	private $materialKeywords;

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
	 * @return Keyword
	 */
	public function setTitle($title) {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get leftParent
	 *
	 * @return int
	 */
	public function getLeftParent() {
		return $this->leftParent;
	}

	/**
	 * Set leftParent
	 *
	 * @param integer $leftParent
	 *
	 * @return Keyword
	 */
	public function setLeftParent($leftParent) {
		$this->leftParent = $leftParent;

		return $this;
	}

	/**
	 * Get rightParent
	 *
	 * @return int
	 */
	public function getRightParent() {
		return $this->rightParent;
	}

	/**
	 * Set rightParent
	 *
	 * @param integer $rightParent
	 *
	 * @return Keyword
	 */
	public function setRightParent($rightParent) {
		$this->rightParent = $rightParent;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getExtraData() {
		return $this->extraData;
	}

	/**
	 * @param array $extraData
	 */
	public function setExtraData($extraData) {
		$this->extraData = $extraData;
	}


}

