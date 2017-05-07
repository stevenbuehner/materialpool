<?php

namespace Modules\MaterialGrabber\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;

/**
 * Bibelstelle
 *
 * @ORM\Table(name="bibelstelle")
 * @ORM\Entity(repositoryClass="Modules\MaterialGrabber\Repositories\BibelstelleRepository")
 */
class Bibelstelle implements BibleVerseInterface {

	/**
	 * @var int
	 *
	 * @ORM\Column(name="start", type="integer")
	 */
	protected $start;
	/**
	 * @var int
	 *
	 * @ORM\Column(name="end", type="integer")
	 */
	protected $end;
	/**
	 * @ORM\ManyToMany(targetEntity="Material", mappedBy="bibelstelle")
	 */
	private $material;

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
	 * @ORM\Column(name="book_id", type="integer")
	 */
	private $bookId;

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
	 * Set fromChapter
	 *
	 * @param integer $fromChapter
	 */
	public function setFromChapter($fromChapter) {
		$this->setFromCombined($fromChapter, $this->getFromVerse());
	}

	public function setFromCombined($chapter, $verse) {
		$this->start = self::getCombi($chapter, $verse);
	}

	public static function getCombi($chapter, $verse) {
		return (int) sprintf('%03d%03d', $chapter, $verse);
	}

	/**
	 * Get fromVerse
	 *
	 * @return int
	 */
	public function getFromVerse() {
		return self::getVerseFromCombi($this->start);
	}

	/**
	 * @param int $chapterVerseNum
	 * @return int
	 */
	public static function getVerseFromCombi($chapterVerseNum) {
		return (int) ($chapterVerseNum % 1000);
	}

	/**
	 * Set fromVerse
	 *
	 * @param integer $fromVerse
	 */
	public function setFromVerse($fromVerse) {
		$this->setFromCombined($this->getFromChapter(), $fromVerse);
	}

	/**
	 * Get fromChapter
	 *
	 * @return int
	 */
	public function getFromChapter() {
		return self::getChapterFromCombi($this->start);
	}

	/**
	 * @param int $chapterVerseNum
	 * @return int
	 */
	public static function getChapterFromCombi($chapterVerseNum) {
		return (int) floor($chapterVerseNum / 1000);
	}

	/**
	 * Set toChapter
	 *
	 * @param integer $toChapter
	 */
	public function setToChapter($toChapter) {
		$this->setToCombined($toChapter, $this->getToVerse());
	}

	public function setToCombined($chapter, $verse) {
		$this->end = self::getCombi($chapter, $verse);
	}

	/**
	 * Get toVerse
	 *
	 * @return int
	 */
	public function getToVerse() {
		return self::getVerseFromCombi($this->end);
	}

	/**
	 * Set toVerse
	 *
	 * @param integer $toVerse
	 */
	public function setToVerse($toVerse) {
		$this->setToCombined($this->getToChapter(), $toVerse);
	}

	/**
	 * Get toChapter
	 *
	 * @return int
	 */
	public function getToChapter() {
		return self::getChapterFromCombi($this->end);
	}

	/**
	 * Returns true if kapFrom / versFrom is equals kapTo / versTo
	 *
	 * @return boolean
	 */
	public function isSingleVers() {
		if ($this->end == $this->start) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function __toString() {
		return "BibleVerse[book={$this->getBookId()} {$this->getFromChapter()},{$this->getFromVerse()}-{$this->getToChapter()},{$this->getToVerse()}]";
	}

	/**
	 * Get bookId
	 *
	 * @return int
	 */
	public function getBookId() {
		return $this->bookId;
	}

	/**
	 * Set bookId
	 *
	 * @param integer $bookId
	 */
	public function setBookId($bookId) {
		$this->bookId = (int) $bookId;
	}

	/**
	 * @param BibleVerseInterface $bv
	 */
	public function insertFromBibleVerseInterface(BibleVerseInterface $bv) {
		$this->setBookId($bv->getBookId());
		$this->setFromCombined($bv->getFromChapter(), $bv->getFromVerse());
		$this->setToCombined($bv->getToChapter(), $bv->getToVerse());
	}

	/**
	 * @param                                               $bookId
	 * @param int                                           $fromChapter
	 * @param int                                           $fromVerse
	 * @param null                                          $toChapter
	 * @param null                                          $toVerse
	 */
	public function setVerse($bookId, $fromChapter, $fromVerse, $toChapter = NULL, $toVerse = NULL) {
		$toChapter = (NULL === $toChapter) ? (int) $fromChapter : $toChapter;
		$toVerse   = (NULL === $toVerse) ? (int) $fromVerse : $toVerse;

		$this->setBookId($bookId);
		$this->setFromCombined($fromChapter, $fromVerse);
		$this->setToCombined($toChapter, $toVerse);
	}

	/**
	 * @return int
	 */
	public function getFrom() {
		return $this->start;
	}

	/**
	 * @return int
	 */
	public function getTo() {
		return $this->end;
	}

	/**
	 * Add material
	 *
	 * @param Material $material
	 *
	 * @return Bibelstelle
	 */
	public function addMaterial(Material $material) {
		$this->material[] = $material;

		return $this;
	}

	/**
	 * Remove material
	 *
	 * @param Material $material
	 */
	public function removeMaterial(Material $material) {
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

	/**
	 * Get bookId
	 *
	 * @return int
	 */
	public function getFromBookId() {
		return $this->getBookId();
	}

	/**
	 * Get bookId
	 *
	 * @return int
	 */
	public function getToBookId() {
		return $this->getBookId();
	}

	/**
	 * Set $fromBookId
	 *
	 * @param int $fromBookId
	 */
	public function setFromBookId($fromBookId) {
		$this->setBookId($fromBookId);
	}

	/**
	 * Set $toBookId
	 *
	 * @param int $toBookId
	 */
	public function setToBookId($toBookId) {
		$this->setBookId($toBookId);
	}


}
