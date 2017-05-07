<?php

namespace Modules\MaterialGrabber\GrabberTemplates;

use Doctrine\ORM\EntityManager;
use Goutte\Client;
use Modules\MaterialGrabber\CrawlerTemplates\Exceptions\MissingCrawlerInstanceException;
use Modules\MaterialGrabber\CrawlerTemplates\Interfaces\CrawlerInterface;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\Services\LinkManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

// putenv('HTTP_PROXY=http://localhost:8888');

/**
 * @property DefaultUserPasswordGrabberConfig $grabberConf
 * @method DefaultUserPasswordGrabberConfig getGrabberConf()
 */
abstract class DefaultGrabber extends AbstractGrabber {

	protected $baseUri               = 'www.someUri.de';
	protected $timeUntilRefreshIndex = '-1 week';
	protected $grabCount             = 0;
	protected $isLoggedIn            = FALSE;
	protected $configValidHash       = NULL;

	protected $container;

	/** @var EntityManager */
	protected $entityManager;

	protected $linkMetaHelper;

	public function __construct(AbstractGrabberConfig $grabberConf, LinkManager $linkManager, ContainerInterface $container) {
		parent::__construct($grabberConf, $linkManager);

		$this->container             = $container;
		$this->entityManager         = $container->get('doctrine.orm.entity_manager');
		$this->timeUntilRefreshIndex = '-1 week';
		$this->baseUri               = 'www.someUri.de';
		$this->linkMetaHelper        = $container->get('link.helper.meta');

		$this->init();
	}

	/**
	 * Todo: Abstract-Todo Initialize at least $this->baseUri, $this->timeUntilRefreshIndex
	 */
	abstract public function init();


	/**
	 * Returns true if all Configurations are valid.
	 * Returns false, if something need to be updated, before the Crawlers can run (i.e. username, password, tokens,
	 * ...)
	 *
	 * @return bool
	 * */
	public function isConfigValid() {

		if ($this->isLoggedIn === TRUE && $this->configValidHash == $this->generateConfigValidHash()) {
			return TRUE;
		}

		$this->isLoggedIn      = $this->login($this->client);
		$this->configValidHash = $this->generateConfigValidHash();

		return $this->isLoggedIn;
	}

	/**
	 * @return string
	 */
	protected function generateConfigValidHash() {
		// Hint: Override, if more or less than thesse too parameters are needed for login
		return md5($this->getGrabberConf()->getUsername() . $this->getGrabberConf()->getPasswort());
	}

	/**
	 * TODO: Abstract-Todo write Login-Skript
	 * TODO: Abstract-Todo if more then $this->getGrabberConf()->getUsername() and $this->getGrabberConf()->getPasswort() is needed to
	 * validate the config override generateConfigValidHash()
	 *
	 * @param Client $client
	 * @return bool logged in
	 */
	abstract public function login(Client $client);

	/**
	 * This function is called, after all grabbing requests.
	 * It can be used to destroy any sessions or what ever.
	 *
	 * @return void
	 */
	public function afterGrabbing() {
		$this->isLoggedIn = FALSE;
		$this->client->getHistory()->clear();
	}

	/**
	 * This function is called before a entity will be grabbed. Usually all Links will first be evaluated and then they
	 * will be grabbed.
	 * Returns true, if the given Link-Entity needs to be grabbed.
	 * Returns false, if the given Link-Entity can be skipped.
	 *
	 * @param Link $link
	 * @param bool $force
	 * @return bool
	 */
	public function needsGrabbing(Link $link, $force = FALSE) {
		if ($link->isIndex()) {

			if ($force === TRUE) {
				return TRUE;
			}

			// Dieser Link
			// a) wurde innerhalb der letzten Woche (default von $timeUntilRefreshIndex)schon erfolgreich eingelesen
			// b) soll nicht via FORCE gerefreshed werden
			// c) Hat einen MD5Cache
			// => Der kann übersprungen werden
			return $this->needsGrabbingHelper($link, $checkStatus = TRUE, $checkEmptyMd5 = TRUE,
											  $checkLastUpdate = TRUE, $useTimeInterval = $this->timeUntilRefreshIndex);

		} else if ($link->getStatus() != Link::$STATUS_FINISHED_SUCCESSFULL) {
			return TRUE;
		} else {
			return !file_exists($link->getFilePath());
		}
	}

	protected function needsGrabbingHelper(Link $link, $checkStatus, $checkEmptyMd5, $checkLastUpdate, $useTimeInterval = '') {
		if ($checkStatus === TRUE && $link->getStatus() !== Link::$STATUS_FINISHED_SUCCESSFULL) {
			return TRUE;
		}

		if ($checkEmptyMd5 === TRUE && empty($link->getMd5Cache())) {
			return TRUE;
		}

		if ($checkLastUpdate === TRUE) {
			if ($link->getLastUpdate() === NULL) {
				return TRUE;

			} else {
				$timeInterval = empty($useTimeInterval) ? $this->timeUntilRefreshIndex : $useTimeInterval;

				if ($link->getLastUpdate() <= new \DateTime($timeInterval)) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 *
	 * This function will be called to perform whatever grabbing action is needed for this link.
	 * For big grabber it is suggested to forward the actual request concrete Crawler.
	 *
	 * @param Link $link
	 * @param bool $onlyCrawlWhenCacheHasChanged
	 */
	public function grabLink(Link $link, $onlyCrawlWhenCacheHasChanged = TRUE) {
		$this->incrementGrabCount();

		/** @var CrawlerInterface $instance */
		$instance = $this->linkMetaHelper->getCrawlerInstance($link);

		if ($instance === FALSE) {
			throw new MissingCrawlerInstanceException('Expected a valid CrawlerInstance for Link-ID: ' . $link->getId());
		}

		$instance->fetch($link, $this->client, $this, $force = $onlyCrawlWhenCacheHasChanged);
	}

	public function incrementGrabCount() {
		return $this->grabCount++;
	}

	/**
	 * @return int
	 */
	public function getGrabCount() {
		return $this->grabCount;
	}

	/**
	 * @param int $grabCount
	 */
	public function setGrabCount($grabCount) {
		$this->grabCount = $grabCount;
	}

	/**
	 * Getter for BaseUri
	 *
	 * @return string
	 */
	public function getBaseUri() {
		return $this->baseUri;

	}

	/**
	 * @param string $baseUri
	 */
	public function setBaseUri($baseUri) {
		$this->baseUri = $baseUri;
	}

	/**
	 * @return string
	 */
	public function getTimeUntilRefreshIndex() {
		return $this->timeUntilRefreshIndex;
	}

	/**
	 * @param string $timeUntilRefreshIndex
	 */
	public function setTimeUntilRefreshIndex($timeUntilRefreshIndex) {
		$this->timeUntilRefreshIndex = $timeUntilRefreshIndex;
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager() {
		return $this->entityManager;
	}

	/**
	 * @return \Modules\MaterialGrabber\CrawlerTemplates\Helper\LinkHelper|object
	 */
	public function getLinkMetaHelper() {
		return $this->linkMetaHelper;
	}

	protected function getDownloadParentPath() {
		return $this->linkManager->getDownloadFilePath() . '/' . $this->getGrabberConf()->getStoragePath();
	}

}