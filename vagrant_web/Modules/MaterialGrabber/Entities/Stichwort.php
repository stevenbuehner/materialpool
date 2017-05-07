<?php

namespace Modules\MaterialGrabber\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Stichwort
 *
 * @ORM\Table(name="stichwort")
 * @ORM\Entity(repositoryClass="Modules\MaterialGrabber\Repositories\StichwortRepository")
 */
class Stichwort {

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
	 * @ORM\Column(name="text", type="string", length=255, unique=true)
	 */
	private $text;
	/**
	 * @ORM\ManyToMany(targetEntity="Material", mappedBy="stichwort")
	 */
	private $material;

	public function __construct() {
		$this->material = new ArrayCollection();
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
	 * Get text
	 *
	 * @return string
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * Set text
	 *
	 * @param string $text
	 *
	 * @return Stichwort
	 */
	public function setText($text) {
		$this->text = $text;

		return $this;
	}

	/**
	 * Add material
	 *
	 * @param \Modules\MaterialGrabber\Entities\Material $material
	 *
	 * @return Stichwort
	 */
	public function addMaterial(\Modules\MaterialGrabber\Entities\Material $material) {
		$this->material[] = $material;

		return $this;
	}

	/**
	 * Remove material
	 *
	 * @param \Modules\MaterialGrabber\Entities\Material $material
	 */
	public function removeMaterial(\Modules\MaterialGrabber\Entities\Material $material) {
		$this->material->removeElement($material);
	}

	/**
	 * Get material
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getMaterial() {
		return $this->material;
	}
}
