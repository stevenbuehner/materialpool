<?php
/**
 * This file was created by  steven
 * Created: 03.07.16 21:29
 * All Rights reserved. No usage without written permission allowed.
 */

namespace Modules\MaterialGrabber\CrawlerTemplates;

use Goutte\Client;
use Modules\MaterialGrabber\CrawlerTemplates\Exceptions\WrongIndexTypeException;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wa72\HtmlPageDom\HtmlPageCrawler;

abstract class BaseDomManipulationCrawler extends BaseCrawler {

	protected $bibleserverHelper;
	protected $bibleVerseHelper;

	public function __construct(ContainerInterface $container) {
		parent::__construct($container);
		$this->bibleserverHelper = $container->get('link.helper.bibleserver');
		$this->bibleVerseHelper  = $container->get('bible_verse.helper');
	}


	/**
	 * @param Link            $link
	 * @param Client          $client
	 * @param AbstractGrabber $grabber
	 * @param bool            $force
	 * @return bool - Success (true) / Failure (false)
	 */
	public function fetch(Link $link, Client $client, AbstractGrabber $grabber, $force) {
		if (!$link->isIndex()) {
			throw new WrongIndexTypeException('Expected Link to be type INDEX. Type FILE given');
		}

		$crawler = $client->request(
			'GET',
			$link->getUrl()
		);

		if ($force == TRUE) {
			$link->setMd5Cache(NULL);
		}

		/** @var Response $response */
		$response = $client->getResponse();
		$newMd5   = md5($response->getContent());

		$crawler = new HtmlPageCrawler($response->getContent());

		// Only run the Crawler, if something in the content has changed to last time
		if ($newMd5 == $link->getMd5Cache()) {
			// Nothing changed, everything is fine!

			return TRUE;
		} else {
			$link->setMd5Cache($newMd5);

			return $this->doCrawling($link, $crawler);
		}
	}

	/**
	 * @param Link            $link
	 * @param HtmlPageCrawler $crawler
	 * @return bool - Success (true) / Failure (false)
	 */
	abstract protected function doCrawling(Link $link, HtmlPageCrawler $crawler);

	/**
	 * @return Helper\BibleserverHelper
	 */
	public function getBibleserverHelper() {
		return $this->bibleserverHelper;
	}

	/**
	 * @param Helper\BibleserverHelper $bibleserverHelper
	 */
	public function setBibleserverHelper($bibleserverHelper) {
		$this->bibleserverHelper = $bibleserverHelper;
	}


}