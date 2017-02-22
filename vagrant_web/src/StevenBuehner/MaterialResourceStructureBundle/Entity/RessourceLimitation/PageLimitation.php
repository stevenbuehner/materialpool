<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity\RessourceLimitation;

class PageLimitation implements ResourceLimitationInterface {

	/**
	 * @var int
	 */
	protected $start;

	/** @var  int */
	protected $end;

	public function __construct() {
		$this->setStart(0);
		$this->setEnd(0);
	}

	/**
	 * @return string
	 */
	public function getAsTextLabel() {
		// TODO: Implement getAsTextLabel() method.
		return 'MISSING IMPLEMENTATION';
	}

	/**
	 * @return string
	 */
	public function getAsHtmlLabel() {
		// TODO: Implement getAsHtmlLabel() method.
		return 'MISSING IMPLEMENTATION';
	}

	/**
	 * @return int
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * @param int $start
	 */
	public function setStart($start) {
		$this->start = $start;
	}

	/**
	 * @return int
	 */
	public function getEnd() {
		return $this->end;
	}

	/**
	 * @param int $end
	 */
	public function setEnd($end) {
		$this->end = $end;
	}

	/**
	 * @return bool
	 */
	public function isSinglePage() {
		return $this->start === $this->end;
	}


}