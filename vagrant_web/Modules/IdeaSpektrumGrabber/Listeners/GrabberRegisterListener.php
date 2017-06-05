<?php

namespace Modules\IdeaSpektrumGrabber\Listeners;

use Modules\IdeaSpektrumBundle\Grabber\GrabberFactory;
use Modules\MaterialGrabber\Events\GrabberRegister;

class GrabberRegisterListener {
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
	 * @param GrabberRegister $event
	 * @return string
	 */
	public function handle(GrabberRegister $event) {
		$event->addGrabberGenerationClass(GrabberFactory::NAME, GrabberFactory::class);
	}
}
