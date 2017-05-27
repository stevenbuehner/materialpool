<?php

namespace Modules\MaterialGrabber\Services;

use Illuminate\Support\Facades\Event;
use Modules\MaterialGrabber\Entities\GrabberConfig;
use Modules\MaterialGrabber\Events\GrabberRegister;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabberConfig;
use Modules\MaterialGrabber\GrabberTemplates\Exceptions\MissingBundleSetupInterfaceException;
use Modules\MaterialGrabber\GrabberTemplates\GrabberSetupInterface;
use Modules\MaterialGrabber\Repositories\ConfigRepository;
use Modules\MaterialGrabber\Repositories\GrabberConfRepository;

class GrabberService {

	protected $grabberCollection;

	/** @var  GrabberConfRepository */
	protected $grabberRepo;

	/** @var ConfigRepository */
	protected $configRepo;

	/**
	 * GrabberService constructor.
	 *
	 */
	public function __construct() {
		$this->grabberCollection = [];

		// Init the Cache
		$this->getRegisteredGrabbersViaEventCall();
	}

	/**
	 * @return AbstractGrabber[]
	 * @throws MissingBundleSetupInterfaceException
	 */
	protected function getRegisteredGrabbersViaEventCall() {
		$allFactoryClasses = $this->getRegisteredGrabbersArrayViaEventCall();
		$allGrabbers       = [];

		foreach ($allFactoryClasses as $grabberName => $grabberFactoryClass) {

			$grabber = $this->getGrabberByName($grabberName, $grabberFactoryClass);

			if ($grabber) {
				$allGrabbers[] = $grabber;
			}
		}

		return $allGrabbers;
	}

	/**
	 * @return string[]
	 */
	protected function getRegisteredGrabbersArrayViaEventCall() {
		$event = new GrabberRegister();
		Event::fire($event);

		return $event->getGrabberGenerationClasses();
	}

	/**
	 * @param string      $grabberName
	 * @param string|NULL $grabberFactoryClass
	 * @return false|AbstractGrabber
	 * @throws MissingBundleSetupInterfaceException
	 */
	public function getGrabberByName($grabberName, $grabberFactoryClass = NULL) {
		$grabber = FALSE;

		if ($this->getGrabberFromCache($grabberName) !== FALSE) {
			$grabber = $this->getGrabberFromCache($grabberName);
		} else {
			// Retrieve FactoryClass
			if ($grabberFactoryClass === NULL) {
				$allGrabbersAvailable = $this->getRegisteredGrabbersArrayViaEventCall();
				if (isset($allGrabbersAvailable[$grabberName])) {
					$grabberFactoryClass = $allGrabbersAvailable[$grabberName];
				}
			}

			if (!empty($grabberFactoryClass)) {
				$grabber = $this->createGrabber($grabberName, $grabberFactoryClass);
			}
		}

		return $grabber;
	}

	/**
	 * @param $grabberName
	 * @return AbstractGrabber|false
	 */
	protected function getGrabberFromCache($grabberName) {

		if (isset($this->grabberCollection[$grabberName])) {
			$grabber = $this->grabberCollection[$grabberName];
		} else {
			$grabber = FALSE;
		}

		return $grabber;
	}

	/**
	 * @param $grabberName
	 * @param $grabberFactoryClass
	 * @return AbstractGrabber
	 * @throws MissingBundleSetupInterfaceException
	 */
	protected function createGrabber($grabberName, $grabberFactoryClass) {
		$grabberConfig = $this->getGrabberConf($grabberName, $grabberFactoryClass);
		$grabber       = $this->createGrabberFromConf($grabberConfig, $grabberFactoryClass);

		$this->setGrabberIntoCache($grabberName, $grabber);

		return $grabber;
	}

	/**
	 * @param $grabberUID
	 * @param $grabberFactoryClass
	 * @return \Modules\MaterialGrabber\GrabberTemplates\AbstractGrabberConfig
	 * @throws MissingBundleSetupInterfaceException
	 */
	protected function getGrabberConf($grabberUID, $grabberFactoryClass) {
		$this->checkIfClassHasInterface($grabberFactoryClass);

		// Load Grabber-Configuration from DB
		$grabberDBConfig = GrabberConfig::where(
			['name' => $grabberUID]
		)->first();

		/** @var GrabberSetupInterface $factory */
		$factory = new $grabberFactoryClass();

		if ($grabberDBConfig) {
			$grabberConf = $factory->generateGrabberConfigFromStoredConfig($grabberDBConfig);
		} else {
			$grabberConf = $factory->generateGrabberConfigFromNoConfig();
		}

		return $grabberConf;
	}

	/**
	 * @param string $className
	 * @throws MissingBundleSetupInterfaceException
	 */
	protected function checkIfClassHasInterface($className) {
		if (!$className || !in_array(GrabberSetupInterface::class, class_implements($className))) {
			throw new MissingBundleSetupInterfaceException();
		}
	}

	protected function createGrabberFromConf(AbstractGrabberConfig $conf, $factoryClassName) {
		$this->checkIfClassHasInterface($factoryClassName);

		/** @var GrabberSetupInterface $factory */
		$factory = new $factoryClassName();
		$grabber = $factory->generateGrabber($conf);

		return $grabber;
	}

	/**
	 * @param                 $grabberName
	 * @param AbstractGrabber $grabber
	 */
	protected function setGrabberIntoCache($grabberName, AbstractGrabber $grabber) {
		$this->grabberCollection[$grabberName] = $grabber;
	}

	public function getAllActiveGrabbers() {
		$allGrabbers = $this->getAllGrabbers();

		foreach ($allGrabbers as $key => $grabber) {
			if (!$grabber->isActive()) {
				unset($allGrabbers[$key]);
			}
		}

		return $allGrabbers;
	}

	/**
	 * @return AbstractGrabber[]
	 * @throws MissingBundleSetupInterfaceException
	 */
	public function getAllGrabbers() {
		return $this->getRegisteredGrabbersViaEventCall();
	}

	public function clearGrabberFromCache($grabberName) {
		if (isset($this->grabberCollection[$grabberName])) {
			unset($this->grabberCollection[$grabberName]);
		}
	}

}