<?php
/**
 * This file was created by  steven
 * Created: 06.03.17 23:26
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\RessourceLimitations;


use App\Http\RessourceLimitations\RessourceLimitationInterface;

class AnkerLimitation implements RessourceLimitationInterface {

	/** @var  string $anker */
	protected $anker = '';

	public function __construct(string $anker) {
		$this->anker = $anker;
	}

	public function getLimitationView() {
		// TODO: Implement getLimitationView() method.
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


}