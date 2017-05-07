<?php
/**
 * This file was created by  steven
 * Created: 03.07.16 13:30
 * All Rights reserved. No usage without written permission allowed.
 */

namespace Modules\MaterialGrabber\CrawlerTemplates\Interfaces;

use Goutte\Client;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;

interface CrawlerInterface {

	/**
	 * @param Link            $link
	 * @param Client          $client
	 * @param AbstractGrabber $grabber
	 * @param bool            $onlyCrawlWhenCacheHasChanged
	 * @return bool - Success (true) / Failure (false)
	 */
	public function fetch(Link $link, Client $client, AbstractGrabber $grabber, $onlyCrawlWhenCacheHasChanged);

}