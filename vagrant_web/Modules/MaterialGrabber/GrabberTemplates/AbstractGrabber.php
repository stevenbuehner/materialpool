<?php

namespace Modules\MaterialGrabber\GrabberTemplates;

use Goutte\Client;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\Services\LinkManager;

abstract class AbstractGrabber {

	/** @var AbstractGrabberConfig */
	protected $grabberConf;
	protected $client;
	protected $linkManager;

	public function __construct(AbstractGrabberConfig $grabberConf, LinkManager $linkManager) {
		$this->grabberConf = $grabberConf;
		$this->client      = new Client();
		$this->linkManager = $linkManager;

		$this->client->setHeader('user-agent',
								 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36');
		$this->client->setHeader('Accept',
								 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8');
		$this->client->setHeader('Cache-Control', 'max-age=0');
		// $this->client->setHeader('Accept-Language', 'de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4,zh;q=0.2,zh-TW;q=0.2,zh-CN;q=0.2');
		// $this->client->setHeader('Upgrade-Insecure-Requests', '1');
	}

	/**
	 * Returns true if the config needs no adjustment.
	 * Returns false, if the config needs to be changed (i.e. password, username, tokens, etc.)
	 *
	 * @return bool
	 */
	abstract public function isConfigValid();

	/**
	 * This function is called, before any grabbing Requests are made.
	 * It can be used to initialize what ever
	 *
	 * @return void
	 */
	abstract public function beforeGrabbing();

	/**
	 * This function is called, after all grabbing requests.
	 * It can be used to destroy any sessions or what ever.
	 *
	 * @return void
	 */
	abstract public function afterGrabbing();

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
	abstract public function needsGrabbing(Link $link, $force = FALSE);

	/** This function will be called to perform whatever grabbing action is needed for this link.
	 * For big grabber it is suggested to forward the actual request concrete Crawler.
	 *
	 * @param Link $link
	 * @param bool $onlyCrawlWhenCacheHasChanged
	 */
	abstract public function grabLink(Link $link, $onlyCrawlWhenCacheHasChanged = TRUE);

	public function getName() {
		return $this->grabberConf->getName();
	}

	public function getAuthor() {
		return $this->grabberConf->getAuthor();
	}

	public function isActive() {
		return $this->grabberConf->isActive();
	}

	public function getDescription() {
		return $this->grabberConf->getDescription();
	}

	/**
	 * @param bool $isActive
	 * @return AbstractGrabber
	 */
	public function setActive($isActive) {
		$this->grabberConf->setActive($isActive);

		return $this;
	}

	/**
	 * @return AbstractGrabberConfig
	 * */
	public function getGrabberConf() {
		return $this->grabberConf;
	}

	/**
	 * @return Client
	 */
	public function getClient() {
		return $this->client;
	}

	/**
	 * @param Client $client
	 */
	public function setClient($client) {
		$this->client = $client;
	}

	/**
	 * @return LinkManager
	 */
	public function getLinkManager() {
		return $this->linkManager;
	}

}