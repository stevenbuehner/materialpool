<?php

namespace Modules\MaterialGrabber\GrabberTemplates\Crawler;

use Doctrine\ORM\EntityManager;
use Goutte\Client;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\Services\LinkManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

abstract class BaseCrawlerHandler {

	/** @var LinkManager $linkManager */
	protected $linkManager;

	/** @var EntityManager */
	protected $entityManager;

	/** @var ContainerInterface */
	protected $container;

	/**
	 * StichwortCrawler constructor.
	 *
	 * @param $linkManager
	 */
	public function __construct(LinkManager $linkManager, EntityManager $entityManager, ContainerInterface $container) {
		$this->linkManager   = $linkManager;
		$this->entityManager = $entityManager;
		$this->container     = $container;
	}


	/**
	 * @param Crawler $crawler
	 * @param Link    $link
	 */
	abstract function crawl(Link $link, Crawler $crawler, Client $client);


	protected function addOptionField(Link $link, $fieldname, $value, $cleanup = FALSE) {
		$options = $link->getOptions();

		if (TRUE === $cleanup && is_string($fieldname)) {
			$fieldname = preg_replace('~([,:;-]|\s)+$~', '', $fieldname);
			$fieldname = preg_replace('~^([,:;-]|\s)+~', '', $fieldname);
		}

		$options[$fieldname] = $value;

		$link->setOptions($options);
	}

	protected function makeLink($href, $currentSiteLink) {
		$schema  = parse_url($currentSiteLink, PHP_URL_SCHEME);
		$baseUri = (!empty($schema) ? $schema . '://' : '') . parse_url($currentSiteLink, PHP_URL_HOST);

		// absolute URL?
		if (NULL !== parse_url($href, PHP_URL_SCHEME)) {
			return $href;
		}

		$uri = $this->cleanupUri($href);

		if ('?' === $uri[0]) {
			return $baseUri . $uri;
		}

		// absolute URL with relative schema
		if (0 === strpos($uri, '//')) {
			return preg_replace('#^([^/]*)//.*$#', '$1', $baseUri) . $uri;
		}

		$baseUri = preg_replace('#^(.*?//[^/]*)(?:\/.*)?$#', '$1', $baseUri);

		// absolute path
		if ('/' === $uri[0]) {
			return $baseUri . $uri;
		}

		// relative path
		$path = parse_url(substr($currentSiteLink, strlen($baseUri)), PHP_URL_PATH);
		$path = $this->canonicalizePath(substr($path, 0, strrpos($path, '/')) . '/' . $uri);

		return $baseUri . ('' === $path || '/' !== $path[0] ? '/' : '') . $path;
	}

	/**
	 * Removes the query string and the anchor from the given uri.
	 *
	 * @param string $uri The uri to clean
	 *
	 * @return string
	 */
	private function cleanupUri($uri) {
		return $this->cleanupQuery($this->cleanupAnchor($uri));
	}

	/**
	 * Remove the query string from the uri.
	 *
	 * @param string $uri
	 *
	 * @return string
	 */
	private function cleanupQuery($uri) {
		if (FALSE !== $pos = strpos($uri, '?')) {
			return substr($uri, 0, $pos);
		}

		return $uri;
	}

	/**
	 * Remove the anchor from the uri.
	 *
	 * @param string $uri
	 *
	 * @return string
	 */
	private function cleanupAnchor($uri) {
		if (FALSE !== $pos = strpos($uri, '#')) {
			return substr($uri, 0, $pos);
		}

		return $uri;
	}


	/**
	 * Returns the canonicalized URI path (see RFC 3986, section 5.2.4).
	 *
	 * @param string $path URI path
	 *
	 * @return string
	 */
	protected function canonicalizePath($path) {
		if ('' === $path || '/' === $path) {
			return $path;
		}

		if ('.' === substr($path, -1)) {
			$path .= '/';
		}

		$output = [];

		foreach (explode('/', $path) as $segment) {
			if ('..' === $segment) {
				array_pop($output);
			} elseif ('.' !== $segment) {
				$output[] = $segment;
			}
		}

		return implode('/', $output);
	}


}