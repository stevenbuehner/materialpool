<?php

namespace Modules\MaterialGrabber\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Material
 *
 * @ORM\Table(name="material")
 * @ORM\Entity(repositoryClass="Modules\MaterialGrabber\Repositories\MaterialRepository")
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
	 * @ORM\Column(name="titel", type="string", length=255)
	 */
	private $titel;
	/**
	 * @var string
	 *
	 * @ORM\Column(name="beschreibung", type="text", nullable=true)
	 */
	private $beschreibung;
	/**
	 * @var string
	 *
	 * @ORM\Column(name="heft", type="string", length=255, nullable=true)
	 */
	private $heft;
	/**
	 * @var string
	 *
	 * @ORM\Column(name="pool", type="string", length=255, nullable=true)
	 */
	private $pool;
	/**
	 * @var Link
	 *
	 * @ORM\OneToOne(targetEntity="Link", inversedBy="material", cascade={"persist", "merge"})
	 */
	private $link;
	/**
	 * @var array
	 *
	 * @ORM\Column(name="autoren", type="array")
	 */
	private $autoren;
	/**
	 *
	 * @ORM\ManyToMany(targetEntity="Stichwort", inversedBy="material", cascade={"persist", "detach", "merge"})
	 * @ORM\JoinTable(name="material_stichwoerter")
	 */
	private $stichwort;
	/**
	 * @ORM\ManyToMany(targetEntity="Bibelstelle", inversedBy="material", cascade={"persist", "detach", "merge"})
	 * @ORM\JoinTable(name="material_bibelstellen")
	 */
	private $bibelstelle;

	public function __construct() {
		$this->autoren     = [];
		$this->stichwort   = new ArrayCollection();
		$this->bibelstelle = new ArrayCollection();
	}

	/**
	 * Get titel
	 *
	 * @return string
	 */
	public function getTitel() {
		return $this->titel;
	}

	/**
	 * Set titel
	 *
	 * @param string $titel
	 *
	 * @return Material
	 */
	public function setTitel($titel) {
		$this->titel = $titel;

		return $this;
	}

	/**
	 * Get beschreibung
	 *
	 * @return string
	 */
	public function getBeschreibung() {
		return $this->beschreibung;
	}

	/**
	 * Set beschreibung
	 *
	 * @param string $beschreibung
	 *
	 * @return Material
	 */
	public function setBeschreibung($beschreibung) {
		$this->beschreibung = $beschreibung;

		return $this;
	}

	/**
	 * Get heft
	 *
	 * @return string
	 */
	public function getHeft() {
		return $this->heft;
	}

	/**
	 * Set heft
	 *
	 * @param string $heft
	 *
	 * @return Material
	 */
	public function setHeft($heft) {
		$this->heft = $heft;

		return $this;
	}

	/**
	 * Get pool
	 *
	 * @return string
	 */
	public function getPool() {
		return $this->pool;
	}

	/**
	 * Set pool
	 *
	 * @param string $pool
	 *
	 * @return Material
	 */
	public function setPool($pool) {
		$this->pool = $pool;

		return $this;
	}

	/**
	 * Get link
	 *
	 * @return \Modules\MaterialGrabber\Entities\Link
	 */
	public function getLink() {
		return $this->link;
	}

	/**
	 * Set link
	 *
	 * @param \Modules\MaterialGrabber\Entities\Link $link
	 *
	 * @return Material
	 */
	public function setLink(\Modules\MaterialGrabber\Entities\Link $link = NULL) {
		if ($this->link !== NULL) {
			$this->link->setMaterial(NULL);
		}

		$this->link = $link;

		if ($link !== NULL) {
			$link->setMaterial($this);
		}

		return $this;
	}

	/**
	 * @param Stichwort[] $stichwoerter
	 */
	public function updateStichwoerter($stichwoerter) {
		// Remove Stichwoerter
		foreach ($this->stichwort->toArray() as $stichwort) {
			$found = FALSE;
			foreach ($stichwoerter as $inSt) {
				if ($stichwort == $inSt) {
					$found = TRUE;
					break;
				}
			}

			if ($found === FALSE) {
				$this->removeStichwort($stichwort);
			}
		}

		foreach ($stichwoerter as $stichwort) {
			$this->addStichwort($stichwort);
		}
	}

	/**
	 * Remove stichwort
	 *
	 * @param \Modules\MaterialGrabber\Entities\Stichwort $stichwort
	 */
	public function removeStichwort(\Modules\MaterialGrabber\Entities\Stichwort $stichwort) {
		$this->stichwort->removeElement($stichwort);
		$stichwort->getMaterial()->removeElement($this);
	}

	/**
	 * Add stichwort
	 *
	 * @param \Modules\MaterialGrabber\Entities\Stichwort $stichwort
	 *
	 * @return Material
	 */
	public function addStichwort(\Modules\MaterialGrabber\Entities\Stichwort $stichwort) {
		if (!$this->stichwort->contains($stichwort)) {
			$this->stichwort[] = $stichwort;
			$stichwort->addMaterial($this);
		}

		return $this;
	}

	/**
	 * Remove all Stichwort-Associations
	 */
	public function clearStichworte() {
		foreach ($this->stichwort as $s) {
			/** @var $s Stichwort */
			$s->removeMaterial($this);
		}
		$this->stichwort->clear();
	}

	/**
	 * Get stichwort
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getStichwort() {
		return $this->stichwort;
	}

	/**
	 * Add bibelstelle
	 *
	 * @param \Modules\MaterialGrabber\Entities\Bibelstelle $bibelstelle
	 *
	 * @return Material
	 */
	public function addBibelstelle(\Modules\MaterialGrabber\Entities\Bibelstelle $bibelstelle) {
		if (!$this->bibelstelle->contains($bibelstelle)) {
			$this->bibelstelle[] = $bibelstelle;
			$bibelstelle->addMaterial($this);
		}

		return $this;
	}

	/**
	 * Remove bibelstelle
	 *
	 * @param \Modules\MaterialGrabber\Entities\Bibelstelle $bibelstelle
	 */
	public function removeBibelstelle(\Modules\MaterialGrabber\Entities\Bibelstelle $bibelstelle) {
		$this->bibelstelle->removeElement($bibelstelle);
		$bibelstelle->removeMaterial($this);
	}

	/**
	 * Remove all Bibelstelle-Associations
	 */
	public function clearBibelstellen() {
		foreach ($this->bibelstelle as $b) {
			/** @var $b Bibelstelle */
			$b->removeMaterial($this);
		}
		$this->bibelstelle->clear();
	}

	/**
	 * Get bibelstelle
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getBibelstelle() {
		return $this->bibelstelle;
	}

	/**
	 * Get autoren
	 *
	 * @return string
	 */
	public function getAutoren() {
		return $this->autoren;
	}

	/**
	 * Set autoren
	 *
	 * @param string $autoren
	 *
	 * @return Material
	 */
	public function setAutoren($autoren) {
		$this->autoren = $autoren;

		return $this;
	}

	public function __toString() {
		// TODO: Implement __toString() method.
		return 'material-id: ' . $this->getId();
	}

	/**
	 * Get id
	 *
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}
}
