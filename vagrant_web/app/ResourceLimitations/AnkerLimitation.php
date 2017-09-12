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

	public function __construct() {
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

	/**
	 * Takes the string, used in the webinterface and extracts all the neccessary limitation data from it
	 *
	 * @return ResourceLimitationInterface
	 */
	public function insertFromWebValue(string $value) {
		$this->setAnker($value);

		return $this;
	}
}