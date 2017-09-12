<?php

namespace App\ResourceLimitations;

use Illuminate\View\View;

interface ResourceLimitationInterface {

	/**
	 * @return View
	 */
	public function getLimitationView();

	/**
	 * @return string
	 */
	public function getLimitationText();

	/** @return array */
	public function toArray();

	/**
	 * Takes the string, used in the webinterface and extracts all the neccessary limitation data from it
	 *
	 * @return ResourceLimitationInterface
	 */
	public function insertFromWebValue(string $value);

}