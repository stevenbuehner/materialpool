<?php

namespace LinkBundle\Events;

use Symfony\Component\EventDispatcher\Event;

class RegisterGrabberEvent extends Event {

	const NAME = 'grabber.register';

	/**
	 * @var array
	 * Contains all absolute classnames of grabbers, that are currently available
	 * Other plugins may hook into this event and add their own grabbers or remove others
	 * array: ($grabberName => $grabberFactoryClass)
	 */
	protected $grabberGenerationClasses = [];

	public function __construct() {
		$this->grabberGenerationClasses = [];
	}

	/**
	 * @return array
	 */
	public function getGrabberGenerationClasses() {
		return $this->grabberGenerationClasses;
	}

	/**
	 * @param array $grabberGenerationClasses
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