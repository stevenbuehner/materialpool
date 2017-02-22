<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MaterialTyp
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="material_typ")
 * @ORM\Entity(repositoryClass="StevenBuehner\MaterialResourceStructureBundle\Repository\MaterialTypeRepository")
 * @see http://symfony.com/doc/master/bundles/StofDoctrineExtensionsBundle/index.html
 */
class MaterialType {
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
	 * @var string
	 *
	 * @ORM\Column(name="description", type="text")
	 */
	private $description;

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
	 * @var int
	 * @Gedmo\TreeLevel
	 * @ORM\Column(name="lvl", type="integer")
	 */
	private $level;

	/**
	 * @Gedmo\TreeRoot
	 * @ORM\ManyToOne(targetEntity="MaterialType")
	 * @ORM\JoinColumn(name="tree_root", referencedColumnName="id", onDelete="CASCADE")
	 */
	private $root;

	/**
	 *
	 * @var MaterialType|NULL
	 * @Gedmo\TreeParent
	 * @ORM\ManyToOne(targetEntity="MaterialType", inversedBy="children")
	 * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
	 */
	private $parent;

	/**
	 * @ORM\OneToMany(targetEntity="MaterialType", mappedBy="parent")
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

	public function setUp() {
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
	 * @return MaterialType
	 */
	public function setTitle($title) {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get description
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Set description
	 *
	 * @param string $description
	 *
	 * @return MaterialType
	 */
	public function setDescription($description) {
		$this->description = $description;

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
	 * @return MaterialType
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
	 * @return MaterialType
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
	 * @return MaterialType
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
	 * @return MaterialType
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
	 * @return MaterialType
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
	 * @return MaterialType
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
	 * @return MaterialType
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
	 * @return MaterialType
	 */
	public function setUpdated($updated) {
		$this->updated = $updated;

		return $this;
	}



}

