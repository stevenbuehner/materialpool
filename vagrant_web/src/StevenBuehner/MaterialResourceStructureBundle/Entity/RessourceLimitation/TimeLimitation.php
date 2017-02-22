<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity\RessourceLimitation;

class TimeLimitation implements ResourceLimitationInterface {

	/**
	 * @var float
	 */
	protected $start;

	/** @var  float */
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
	 * @return float
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * @param float $start
	 */
	public function setStart($start) {
		$this->start = $start;
	}

	/**
	 * @return float
	 */
	public function getEnd() {
		return $this->end;
	}

	/**
	 * @param float $end
	 */
	public function setEnd($end) {
		$this->end = $end;
	}


}