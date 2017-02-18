<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MaterialTyp
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="material_typ")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
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
	 */
	public function setLeft($left) {
		$this->left = $left;
	}

	/**
	 * @return int
	 */
	public function getRight() {
		return $this->right;
	}

	/**
	 * @param int $right
	 */
	public function setRight($right) {
		$this->right = $right;
	}

	/**
	 * @return mixed
	 */
	public function getLevel() {
		return $this->level;
	}

	/**
	 * @param mixed $level
	 */
	public function setLevel($level) {
		$this->level = $level;
	}

	/**
	 * @return mixed
	 */
	public function getRoot() {
		return $this->root;
	}

	/**
	 * @param mixed $root
	 */
	public function setRoot($root) {
		$this->root = $root;
	}

	/**
	 * @return mixed
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * @param mixed $parent
	 */
	public function setParent($parent) {
		$this->parent = $parent;
	}



}

