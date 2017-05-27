<?php

namespace Modules\MaterialGrabber\Events;

use Illuminate\Queue\SerializesModels;

class GrabberRegister {
	use SerializesModels;

	/**
	 * @var string[]
	 * Contains all absolute classnames of grabbers, that are currently available
	 * Other plugins may hook into this event and add their own grabbers or remove others
	 * array: ($grabberName => $grabberFactoryClass)
	 */
	protected $grabberGenerationClasses = [];

	/**
	 * Create a new event instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->grabberGenerationClasses = [];
	}

	/**
	 * Get the channels the event should be broadcast on.
	 *
	 * @return array
	 */
	public function broadcastOn() {
		return [];
	}

	/**
	 * @return string[]
	 */
	public function getGrabberGenerationClasses() {
		return $this->grabberGenerationClasses;
	}

	/**
	 * @param string[] $grabberGenerationClasses
	 */
	public function setGrabberGenerationClasses($grabberGenerationClasses) {
		$this->grabberGenerationClasses = $grabberGenerationClasses;
	}

	public function addGrabberGenerationClass($grabberName, $grabberFactoryClass) {
		if (!isset($this->grabberGenerationClasses[$grabberName])) {
			$this->grabberGenerationClasses[$grabberName] = $grabberFactoryClass;
		}
	}

	public function removeGrabberGenerationClass($grabberName) {
		if (isset($this->grabberGenerationClasses[$grabberName])) {
			unset($grabberName);
		}
	}
}
