<?php

namespace Modules\MaterialGrabber\GrabberTemplates;

use Modules\MaterialGrabber\Entities\GrabberConfig;
use Symfony\Component\Console\Question\Question;

abstract class AbstractGrabberConfig {

	/** @var  GrabberConfig */
	protected $grabberConfig;


	public function __construct(GrabberConfig $grabberConfig) {
		$this->setGrabberConfig($grabberConfig);

		if (!$grabberConfig->exists) {
			$grabberConfig->saveOrFail();
		}
	}

	/**
	 * Expects an associative array of Questions.
	 * The result of all Questions will be sent back to saveParameter($keyOfAssociativeArray, $userResultValue)
	 *
	 * @return Question[]
	 */
	abstract function getConfigQuestions();

	/** @return string */
	public function getName() {
		return $this->grabberConfig->name;
	}

	/** @ereturn int */
	public function getId() {
		return $this->grabberConfig->id;
	}

	/** @return string */
	public function getAuthor() {
		return $this->grabberConfig->author;
	}

	/** @return bool */
	public function isActive() {
		return $this->grabberConfig->is_active;
	}

	/**
	 * @param bool $isActive
	 * @return AbstractGrabberConfig
	 */
	public function setActive($isActive) {
		$this->getGrabberConfig()->is_active = $isActive;

		return $this;
	}

	/**
	 * @return GrabberConfig
	 */
	public function getGrabberConfig() {
		return $this->grabberConfig;
	}

	/**
	 * @param GrabberConfig $grabberConfig
	 */
	public function setGrabberConfig(GrabberConfig $grabberConfig) {
		$this->grabberConfig = $grabberConfig;
	}

	/** @return string */
	public function getDescription() {
		return $this->grabberConfig->description;
	}

	/**
	 * @param \DateTime|NULL $lastRun
	 * @return AbstractGrabberConfig
	 */
	public function setLastRun($lastRun = NULL) {
		if ($lastRun === NULL) {
			$lastRun = new \DateTime('now');
		}

		$this->grabberConfig->last_complete_run = $lastRun;

		return $this;
	}

	/**
	 * @return \DateTime|NULL
	 */
	public function getLastRun() {
		return $this->grabberConfig->last_complete_run;
	}

	/**
	 * Asserts an existing parameter in the Config and removes it
	 *
	 * @param string $name
	 * @throws ConfigParameterDoesNotExistException
	 */
	public function removeParameter($name) {
		$this->grabberConfig->removeConfigValueByName($name);
	}


	/**
	 * Asserts an existing parameter in the Config and updates it
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setParameter($name, $value) {
		$this->getGrabberConfig()->setConfigValue($name, $value);
	}


	/**
	 * Asserts that the parameter with the given key exists and returns it.
	 *
	 * @param string      $name
	 * @param string|NULL $default
	 * @return string|null
	 */
	public function getParameter($name, $default = NULL) {
		$name = (string) $name;

		return $this->grabberConfig->getConfigValueByName($name, $default);
	}

	public function save() {
		$this->getGrabberConfig()->save();
	}

}