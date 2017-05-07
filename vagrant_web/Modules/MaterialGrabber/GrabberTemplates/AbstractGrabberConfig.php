<?php

namespace Modules\MaterialGrabber\GrabberTemplates;

use Modules\MaterialGrabber\Entities\ConfigValue;
use Modules\MaterialGrabber\Entities\GrabberConf;
use Symfony\Component\Console\Question\Question;

abstract class AbstractGrabberConfig {

	/** @var  GrabberConf */
	protected $grabberInfo;


	public function __construct(GrabberConf $grabberInfo) {
		$this->setGrabberInfo($grabberInfo);
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
		return $this->grabberInfo->getName();
	}

	/** @ereturn int */
	public function getId() {
		return $this->grabberInfo->getId();
	}

	/** @return string */
	public function getAuthor() {
		return $this->grabberInfo->getAuthor();
	}

	/** @return bool */
	public function isActive() {
		return $this->grabberInfo->getIsActive();
	}

	/**
	 * @param bool $isActive
	 * @return AbstractGrabberConfig
	 */
	public function setActive($isActive) {
		$this->getGrabberInfo()->setIsActive($isActive);

		return $this;
	}

	/**
	 * @return GrabberConf
	 */
	public function getGrabberInfo() {
		return $this->grabberInfo;
	}

	/**
	 * @param GrabberConf $grabberInfo
	 */
	public function setGrabberInfo(GrabberConf $grabberInfo) {
		$this->grabberInfo = $grabberInfo;
	}

	/** @return string */
	public function getDescription() {
		return $this->grabberInfo->getDescription();
	}

	/**
	 * @param \DateTime|NULL $lastRun
	 * @return AbstractGrabberConfig
	 */
	public function setLastRun($lastRun = NULL) {
		if ($lastRun === NULL) {
			$lastRun = new \DateTime('now');
		}

		$this->grabberInfo->setLastRun($lastRun);

		return $this;
	}

	/**
	 * @return \DateTime|NULL
	 */
	public function getLastRun() {
		return $this->grabberInfo->getLastRun();
	}

	/**
	 * Asserts an existing parameter in the Config and removes it
	 *
	 * @param string $key
	 * @throws ConfigParameterDoesNotExistException
	 */
	public function removeParameter($key) {
		$param = $this->getParameter($key);
		$this->grabberInfo->getConfigValues()->removeElement($param);
	}

	/**
	 * Asserts that the parameter with the given key exists and returns it.
	 *
	 * @param string $key
	 * @return ConfigValue
	 * @throws ConfigParameterDoesNotExistException
	 */
	public function getParameter($key) {
		$key = (string) $key;

		foreach ($this->grabberInfo->getConfigValues() as $param) {
			if ($param->getName() == $key) {
				return $param;
			}
		}

		throw new ConfigParameterDoesNotExistException("The requested Config-Parameter '{$key}' does not exist");
	}

	/**
	 * Create or updates the parameter, whatever is necessary
	 *
	 * @param $key
	 * @param $value
	 * @return ConfigValue
	 */
	public function saveParameter($key, $value) {
		if ($this->hasParameter($key)) {
			$param = $this->updateParameter($key, $value);
		} else {
			$param = $this->addParameter($key, $value);
		}

		return $param;
	}

	/**
	 * @param bool $key
	 */
	public function hasParameter($key) {
		foreach ($this->grabberInfo->getConfigValues() as $param) {
			if ($param->getName() == $key) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Asserts an existing parameter in the Config and updates it
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @return ConfigValue
	 * @throws ConfigParameterDoesNotExistException
	 */
	public function updateParameter($key, $value) {
		$param = $this->getParameter($key);
		$param->setValue($value);

		return $param;
	}

	/**
	 *
	 * Adds a Parameter. Asserts, that the Parameter does not exist yet!
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param        ConfigValue
	 */
	public function addParameter($key, $value) {
		$param = new ConfigValue();
		$param->setName($key);
		$param->setValue($value);
		$param->setGrabber($this->grabberInfo);
		$this->grabberInfo->getConfigValues()->add($param);

		return $param;
	}

	/**
	 * Returns the stored value of the parameter or the $defaultValue, if the parameterKey does not exist in the db
	 *
	 * @param string $paramKey
	 * @param mixed  $defaultValue
	 * @return null|\stdClass
	 */
	public function getParameterValueOrDefault($paramKey, $defaultValue = NULL) {
		if ($this->hasParameter($paramKey)) {
			return $this->getParameter($paramKey)->getValue();
		} else {
			return $defaultValue;
		}
	}


}