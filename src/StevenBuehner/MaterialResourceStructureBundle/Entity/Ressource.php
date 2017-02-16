<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ressource
 *
 * @ORM\Table(name="ressource")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="className", type="string")
 * @ORM\DiscriminatorMap({"url" = "RessourceUrl", "text" = "RessourceText", "file" = "RessourceFile", "audio" = "RessourceAudio", "video" = "RessourceVideo", "image" = "RessourceImage", "doc" = "RessourceDocument"})
 * @ORM\Entity(repositoryClass="StevenBuehner\MaterialResourceStructureBundle\Repository\RessourceRepository")
 */
abstract class Ressource {
	/**
	 * @var array
	 *
	 * @ORM\Column(name="extraData", type="array")
	 */
	protected $extraData;
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
	 * @ORM\Column(name="contentHash", type="string", length=20, nullable=true)
	 */
	private $contentHash = NULL;
	/**
	 * @var string
	 *
	 * @ORM\Column(name="path", type="text", nullable=false)
	 */
	private $path;
	/**
	 * @var ArrayCollection
	 *
	 * @ORM\OneToMany(targetEntity="Material", mappedBy="resource")
	 */
	private $materials;

	public function __construct() {
		$this->extraData = [];
		$this->materials = new ArrayCollection();
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
	 * Get contentHash
	 *
	 * @return string
	 */
	public function getContentHash() {
		return $this->contentHash;
	}

	/**
	 * Set contentHash
	 *
	 * @param string $contentHash
	 *
	 * @return Ressource
	 */
	public function setContentHash($contentHash) {
		$this->contentHash = $contentHash;

		return $this;
	}

	/**
	 * Get path
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Set path
	 *
	 * @param string $path
	 *
	 * @return Ressource
	 */
	public function setPath($path) {
		$this->path = $path;

		return $this;
	}

	/**
	 * Get extraData
	 *
	 * @return array
	 */
	public function getExtraData() {
		return $this->extraData;
	}

	/**
	 * Set extraData
	 *
	 * @param array $extraData
	 *
	 * @return Ressource
	 */
	public function setExtraData($extraData) {
		$this->extraData = $extraData;

		return $this;
	}
}

