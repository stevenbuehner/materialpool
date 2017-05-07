<?php

namespace Modules\MaterialGrabber\Services;

use Doctrine\ORM\EntityManager;
use LinkBundle\Events\RegisterGrabberEvent;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabberConfig;
use Modules\MaterialGrabber\GrabberTemplates\Exceptions\MissingBundleSetupInterfaceException;
use Modules\MaterialGrabber\GrabberTemplates\GrabberSetupInterface;
use Modules\MaterialGrabber\Repositories\ConfigRepository;
use Modules\MaterialGrabber\Repositories\GrabberConfRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GrabberService {

	protected $grabberCollection;

	/** @var EntityManager */
	protected $entityManager;

	/** @var  EventDispatcherInterface */
	protected $dispatcher;

	/** @var  GrabberConfRepository */
	protected $grabberRepo;

	/** @var ConfigRepository */
	protected $configRepo;

	/** @var  ContainerInterface */
	protected $container;

	/** @var LinkManager */
	protected $linkManager;

	/**
	 * GrabberService constructor.
	 *
	 * @param EntityManager            $entityManager
	 * @param EventDispatcherInterface $dispatcher
	 * @param LinkManager              $linkManager
	 * @param ContainerInterface       $container
	 */
	public function __construct(EntityManager $entityManager, EventDispatcherInterface $dispatcher, LinkManager $linkManager, ContainerInterface $container) {
		$this->entityManager     = $entityManager;
		$this->dispatcher        = $dispatcher;
		$this->container         = $container;
		$this->linkManager       = $linkManager;
		$this->grabberCollection = [];

		// Init the Cache
		$this->getRegisteredGrabbersViaEventCall();
	}

	/**
	 * @return AbstractGrabber[]
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
		$event = new RegisterGrabberEvent();
		$this->dispatcher->dispatch($event::NAME, $event);

		return $event->getGrabberGenerationClasses();
	}

	/**
	 * @param string      $grabberName
	 * @param string|NULL $grabberFactoryClass
	 * @return false|AbstractGrabber
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

		/** @var GrabberSetupInterface $factory */
		$repo            = $this->getBundleRepository();
		$grabberDBConfig = $repo->getGrabberByName($grabberUID);
		$factory         = new $grabberFactoryClass();

		if ($grabberDBConfig) {
			$grabberConf = $factory->generateGrabberConfigFromStoredConfig($grabberDBConfig, $this->container);
		} else {
			$grabberConf = $factory->generateGrabberConfigFromNoConfig($this->container);
			$this->entityManager->persist($grabberConf->getGrabberInfo());
			$this->entityManager->flush();
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

	/** @return GrabberConfRepository */
	protected function getBundleRepository() {
		if (!$this->grabberRepo) {
			$this->grabberRepo = $this->entityManager->getRepository('LinkBundle:GrabberConf');
		}

		return $this->grabberRepo;
	}

	protected function createGrabberFromConf(AbstractGrabberConfig $conf, $factoryClassName) {
		$this->checkIfClassHasInterface($factoryClassName);

		/** @var GrabberSetupInterface $factory */

		$factory = new $factoryClassName();
		$grabber = $factory->generateGrabber($conf, $this->linkManager, $this->container);

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
	 */
	public function getAllGrabbers() {
		return $this->getRegisteredGrabbersViaEventCall();
	}

	public function getAllInactiveGrabbers() {

	}

	public function clearGrabberFromCache($grabberName) {
		if (isset($this->grabberCollection[$grabberName])) {
			unset($this->grabberCollection[$grabberName]);
		}
	}

	/** @return ConfigRepository */
	protected function getBundleConfigRepository() {
		if (!$this->configRepo) {
			$this->configRepo = $this->entityManager->getRepository('LinkBundle:ConfigValue');
		}

		return $this->configRepo;
	}

}