<?php
namespace App\RessourceLimitations;

use Illuminate\View\View;

interface RessourceLimitationInterface {

	/**
	 * @return View
	 */
	public function getLimitationView();


}