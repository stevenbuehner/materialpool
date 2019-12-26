<?php

namespace App\Jobs;

use App\Events\MaterialWasChanged;

class CreateMaterialPreviewCache {

	/**
	 * Create the event listener.
	 *
	 * @return void
	 */
	public function __construct() {
		//
	}

	/**
	 * Handle the event.
	 *
	 * @param MaterialWasChanged $event
	 * @return void
	 */
	public function handle(MaterialWasChanged $event) {
		//
	}
}
