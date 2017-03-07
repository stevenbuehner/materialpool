<?php
/**
 * This file was created by  steven
 * Created: 06.03.17 23:26
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\ResourceLimitations;

class AnkerLimitation implements ResourceLimitationInterface {

	/** @var  string $anker */
	protected $anker = '';

	public function __construct(string $anker) {
		$this->anker = $anker;
	}

	function jsonSerialize() {
		return ['anker' => $this->getAnker()];
	}

	/**
	 * @return string
	 */
	public function getAnker(): string {
		return $this->anker;
	}

	/**
	 * @param string $anker
	 */
	public function setAnker(string $anker) {
		$this->anker = $anker;
	}

	public function getLimitationView() {
		// TODO: Implement getLimitationView() method.
	}


	/** @return array */
	public function toArray() {
		return ['anker' => $this->getAnker()];
	}
}