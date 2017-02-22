<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Keyword
 *
 * @ORM\Table(name="keyword")
 * @ORM\Entity(repositoryClass="StevenBuehner\MaterialResourceStructureBundle\Repository\KeywordRepository")
 *
 * @Gedmo\Tree(type="nested")
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="className", type="string")
 * @ORM\DiscriminatorMap({"keyword" = "Keyword", "person" = "KeywordPerson", "place" = "KeywordPlace"})
 */
class Keyword {
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
	 * @var string
	 *
	 * @ORM\Column(name="title", type="string", length=255)
	 */
	private $title;

	/**
	 * One Keyword has many MaterialKeywords
	 *
	 * @var MaterialKeyword
	 * @ORM\OneToMany(targetEntity="MaterialKeyword", mappedBy="keyword")
	 */
	private $materialKeywords;

	/**
	 * "left" ausgeschrieben ist ein SQL geschützter Wort :/
	 *
	 * @var int
	 * @Gedmo\TreeLeft
	 * @ORM\Column(name="lft", type="integer")
	 */
	private $left;

	/**
	 * "right" ausgeschrieben ist ein SQL geschützter Wort :/
	 *
	 * @var int
	 * @Gedmo\TreeRight)
	 * @ORM\Column(name="rght", type="integer")
	 */
	private $right;

	/**
	 * "level" ausgeschrieben ist ein SQL geschützter Wort :/
	 *
	 * @Gedmo\TreeLevel
	 * @ORM\Column(name="lvl", type="integer")
	 */
	private $level;

	/**
	 * @Gedmo\TreeRoot
	 * @ORM\ManyToOne(targetEntity="Keyword")
	 * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
	 */
	private $root;

	/**
	 * @Gedmo\TreeParent
	 * @ORM\ManyToOne(targetEntity="Keyword", inversedBy="children")
	 * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
	 */
	private $parent;

	/**
	 * @ORM\OneToMany(targetEntity="Keyword", mappedBy="parent")
	 * @ORM\OrderBy({"left" = "ASC"})
	 */
	private $children;

	/**
	 * @Gedmo\Timestampable(on="create")
	 * @ORM\Column(type="datetime")
	 */
	private $created;

	/**
	 * @Gedmo\Timestampable(on="update")
	 * @ORM\Column(type="datetime")
	 */
	private $updated;

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
	 * @return array
	 */
	public function getExtraData() {
		return $this->extraData;
	}

	/**
	 * @param array $extraData
	 * @return Keyword
	 */
	public function setExtraData($extraData) {
		$this->extraData = $extraData;

		return $this;
	}

	/**
	 * @return MaterialKeyword
	 */
	public function getMaterialKeywords() {
		return $this->materialKeywords;
	}

	/**
	 * @param MaterialKeyword $materialKeywords
	 * @return Keyword
	 */
	public function setMaterialKeywords($materialKeywords) {
		$this->materialKeywords = $materialKeywords;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLeft() {
		return $this->left;
	}

	/**
	 * @param int $left
	 * @return Keyword
	 */
	public function setLeft($left) {
		$this->left = $left;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRight() {
		return $this->right;
	}

	/**
	 * @param int $right
	 * @return Keyword
	 */
	public function setRight($right) {
		$this->right = $right;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLevel() {
		return $this->level;
	}

	/**
	 * @param int $level
	 * @return Keyword
	 */
	public function setLevel($level) {
		$this->level = $level;

		return $this;
	}

	/**
	 * @return MaterialType|NULL
	 */
	public function getRoot() {
		return $this->root;
	}

	/**
	 * @param MaterialType|NULL $root
	 * @return Keyword
	 */
	public function setRoot($root) {
		$this->root = $root;

		return $this;
	}

	/**
	 * @return MaterialType|NULL
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * @param MaterialType $parent|NULL
	 * @return Keyword
	 */
	public function setParent($parent) {
		$this->parent = $parent;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getChildren() {
		return $this->children;
	}

	/**
	 * @param mixed $children
	 * @return Keyword
	 */
	public function setChildren($children) {
		$this->children = $children;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCreated() {
		return $this->created;
	}

	/**
	 * @param mixed $created
	 * @return Keyword
	 */
	public function setCreated($created) {
		$this->created = $created;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getUpdated() {
		return $this->updated;
	}

	/**
	 * @param mixed $updated
	 * @return Keyword
	 */
	public function setUpdated($updated) {
		$this->updated = $updated;

		return $this;
	}


}

