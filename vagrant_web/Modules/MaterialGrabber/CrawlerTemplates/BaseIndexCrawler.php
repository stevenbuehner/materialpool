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
use Symfony\Component\DomCrawler\Crawler;

abstract class BaseIndexCrawler extends BaseCrawler {

	/**
	 * @param Link            $link
	 * @param Client          $client
	 * @param AbstractGrabber $grabber
	 * @param bool            $onlyCrawlWhenCacheHasChanged
	 * @return bool - Success (true) / Failure (false)
	 */
	public function fetch(Link $link, Client $client, AbstractGrabber $grabber, $onlyCrawlWhenCacheHasChanged) {
		if (!$link->isIndex()) {
			throw new WrongIndexTypeException('Expected Link to be type INDEX. Type FILE given');
		}

		$force = !$onlyCrawlWhenCacheHasChanged;

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
	 * @param Link    $storyStartLink
	 * @param Crawler $crawler
	 * @return bool - Success (true) / Failure (false)
	 */
	abstract protected function doCrawling(Link $storyStartLink, Crawler $crawler);

	/**
	 * @param Link $parentLink
	 * @param      $newUri
	 * @return FALSE|Link
	 */
	protected function getFollowUpFileLink(Link $parentLink, $newUri) {

		if ($link = $this->getFollowUpIndexLink($parentLink, $newUri)) {
			$link->setIndex(FALSE);

			return $link;
		}

		return FALSE;
	}

	/**
	 * @param Link $parentLink
	 * @param      $newUri
	 * @return FALSE|Link
	 */
	protected function getFollowUpIndexLink(Link $parentLink, $newUri) {

		if (!$this->linkManager->hasLink($parentLink->getGrabber(), $newUri)) {
			$l = new Link();
			$l->setUrl($newUri);
			$l->setIndex(TRUE);
			$l->setGrabber($parentLink->getGrabber());
			$l->setStatus(Link::$STATUS_WAITING);
			$l->setPriority($parentLink->getPriority() - 10);

			return $l;
		}

		return FALSE;
	}

}