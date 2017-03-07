<?php
namespace App\ResourceLimitations;

use Illuminate\View\View;

interface ResourceLimitationInterface {

	/**
	 * @return View
	 */
	public function getLimitationView();

	/** @return array */
	public function toArray();


}