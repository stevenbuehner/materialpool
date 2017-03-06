<?php
/**
 * This file was created by  steven
 * Created: 06.03.17 23:26
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\RessourceLimitations;


use App\Http\RessourceLimitations\RessourceLimitationInterface;

class PageLimitation implements RessourceLimitationInterface {

	/** @var int $start */
	protected $start = 0;

	/** @var int $end */
	protected $end = 0;

	/**
	 * TimeLimitation constructor.
	 *
	 * @param int $start
	 * @param int $end
	 */
	public function __construct(int $start = 0, int $end = 999999) {
		$this->start = $start;
		$this->end   = $end;
	}


	public function getLimitationView() {
		// TODO: Implement getLimitationView() method.
	}

	/**
	 * @return int
	 */
	public function getStart(): int {
		return $this->start;
	}

	/**
	 * @param int $start
	 */
	public function setStart(int $start) {
		$this->start = $start;
	}

	/**
	 * @return int
	 */
	public function getEnd(): int {
		return $this->end;
	}

	/**
	 * @param int $end
	 */
	public function setEnd(int $end) {
		$this->end = $end;
	}

}