<?php
namespace App\Http\RessourceLimitations;

use Illuminate\View\View;

interface RessourceLimitationInterface {

	/**
	 * @return View
	 */
	public function getLimitationView();

}