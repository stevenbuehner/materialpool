<?php

namespace Modules\MaterialGrabber\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * GrabberBundle
 *
 * @ORM\Table(name="grabber")
 * @ORM\Entity(repositoryClass="Modules\MaterialGrabber\Repositories\GrabberConfRepository")
 */
class GrabberConf {
	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;


	/**
	 * @var  ArrayCollection
	 *
	 * @ORM\OneToMany(targetEntity="ConfigValue", mappedBy="grabber", cascade={"persist",
	 *     "remove", "merge"}, orphanRemoval=true)
	 */
	protected $configValues;

	/**
	 * @var bool
	 *
	 * @ORM\Column(name="isActive", type="boolean")
	 */
	protected $isActive;

	/**
	 * @var ArrayCollection
	 *
	 * @ORM\OneToMany(targetEntity="Modules\MaterialGrabber\Entities\Link", mappedBy="grabber", cascade={"persist", "remove",
	 *     "merge"}, orphanRemoval=true)
	 */
	protected $links;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="description", type="text")
	 */
	protected $description;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="author", type="string", length=255)
	 */
	protected $author;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="name", type="string", length=255)
	 */
	protected $name;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="last_run", type="datetime", nullable=true)
	 */
	protected $lastRun;


	public function __construct() {
		$this->configValues = new ArrayCollection();
		$this->links        = new ArrayCollection();
		$this->name         = 'Default Name';
		$this->author       = 'Steven Bühner';
		$this->description  = 'Default description';
		$this->isActive     = FALSE;
		$this->lastRun      = NULL;
	}

	/**
	 * Get isActive
	 *
	 * @return bool
	 */
	public function getIsActive() {
		return $this->isActive;
	}

	/**
	 * Set isActive
	 *
	 * @param boolean $isActive
	 *
	 * @return GrabberConf
	 */
	public function setIsActive($isActive) {
		$this->isActive = $isActive;

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
	 * @return GrabberConf
	 */
	public function setDescription($description) {
		$this->description = $description;

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
	 * @return GrabberConf
	 */
	public function setAuthor($author) {
		$this->author = $author;

		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 *
	 * @return GrabberConf
	 */
	public function setName($name) {
		$this->name = $name;

		return $this;
	}

	/**
	 * @return ArrayCollection
	 */
	public function getLinks() {
		return $this->links;
	}

	/**
	 * @param ArrayCollection $links
	 */
	public function setLinks($links) {
		$this->links = $links;
	}

	public function __toString() {
		$vars                 = get_object_vars($this);
		$vars['configValues'] = [];

		/** @var ConfigValue $configValue */
		foreach ($this->getConfigValues() as $configValue) {
			$vars['configValues'][] = $configValue->getName() . ' -> ' . $configValue->getValue() . '( ' . $configValue->getId() . '), grabber: ' . $configValue->getGrabber()
																																								->getId();
		}

		$vars['links'] = '(...)';

		// $vars                 = array_keys($vars);

		return (string) print_r($vars, TRUE);
	}

	/**
	 * @return ArrayCollection
	 */
	public function getConfigValues() {
		return $this->configValues;
	}

	/**
	 * @param ArrayCollection $configValues
	 */
	public function setConfigValues($configValues) {
		$this->configValues = $configValues;
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
	 * @return \DateTime|NULL
	 */
	public function getLastRun() {
		return $this->lastRun;
	}

	/**
	 * @param \DateTime $lastRun
	 */
	public function setLastRun($lastRun) {
		$this->lastRun = $lastRun;
	}

}

