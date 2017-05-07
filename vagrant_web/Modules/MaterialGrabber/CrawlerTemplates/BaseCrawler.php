<?php

namespace Modules\MaterialGrabber\CrawlerTemplates;

use Doctrine\ORM\EntityManager;
use Modules\MaterialGrabber\CrawlerTemplates\Interfaces\CrawlerInterface;
use Modules\MaterialGrabber\Services\LinkManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

abstract class BaseCrawler implements CrawlerInterface {

	/** @var LinkManager $linkManager */
	protected $linkManager;

	/** @var EntityManager */
	protected $entityManager;

	/** @var ContainerInterface */
	protected $container;

	/**
	 * @var Helper\LinkHelper
	 */
	protected $linkMetaHelper;


	/**
	 * BaseCrawler constructor
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container) {
		$this->container      = $container;
		$this->linkManager    = $container->get('link.linkmanager');
		$this->entityManager  = $container->get('doctrine.orm.entity_manager');
		$this->linkMetaHelper = $container->get('link.helper.meta');
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager() {
		return $this->entityManager;
	}

	/**
	 * @param EntityManager $entityManager
	 */
	public function setEntityManager($entityManager) {
		$this->entityManager = $entityManager;
	}

	/**
	 * @return LinkManager
	 */
	public function getLinkManager() {
		return $this->linkManager;
	}

	/**
	 * @param LinkManager $linkManager
	 */
	public function setLinkManager($linkManager) {
		$this->linkManager = $linkManager;
	}

	/**
	 * @return Helper\LinkHelper
	 */
	public function getLinkMetaHelper() {
		return $this->linkMetaHelper;
	}

	/**
	 * @param Helper\LinkHelper $linkMetaHelper
	 */
	public function setLinkMetaHelper($linkMetaHelper) {
		$this->linkMetaHelper = $linkMetaHelper;
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

	protected function createDirIfNotExistant($target) {
		// Create directory structure
		$dir = dirname($target);
		if (!empty($dir) && !file_exists($dir)) {
			mkdir($dir, 0777, $recursive = TRUE);
		}
	}

	protected function getRelativePath($from, $to) {
		$from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
		$to   = is_dir($to) ? rtrim($to, '\/') . '/' : $to;
		$from = str_replace('\\', '/', $from);
		$to   = str_replace('\\', '/', $to);

		$from    = explode('/', $from);
		$to      = explode('/', $to);
		$relPath = $to;

		foreach ($from as $depth => $dir) {
			// find first non-matching dir
			if ($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if ($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath   = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}

		return implode('/', $relPath);
	}

	/**
	 * @param string $string
	 * @return string
	 */
	protected function cleanUpString($string) {
		$string = $this->replaceMutatedSpaceChars($string);
		$string = $this->replaceInkonsistentNewLineChars($string);
		$string = $this->removeHtmlLeftovers($string);
		$string = trim($string);

		return $string;
	}

	/**
	 * @see http://heavydots.com/blog/when-the-white-space-became-a-beast
	 * @param string $string
	 * @return string
	 */
	protected function replaceMutatedSpaceChars($string) {
		// Define the white beast
		$white_beast = chr(194) . chr(160);

		// Replace it with a normal space
		$string = str_replace($white_beast, ' ', $string);

		return $string;
	}

	/**
	 * Replaces \r and \r\n with \n
	 *
	 * @param $string
	 * @return string
	 */
	protected function replaceInkonsistentNewLineChars($string) {
		/*
			see: http://stackoverflow.com/questions/2018668/php-r-and-n-same-thing
			UNIX / Linux use \n for linebreaks
			Mac (before OSX) used \r
			And windows uses a combinaison of both
			PHP has just kept that behavior -- so it can work with those different OSes and their files.

		Also, note:
		They are not exactly the same thing:
			\r is Carriage return
			\n is Newline
		*/

		/*
		 * see: 		// see: http://stackoverflow.com/questions/17089742/why-chr-function-at-the-end-of-echo
		 * chr(13) is equivalent to "\r" and chr(10) is equivalent to "\n".
		 * So, $crlf = chr(13) . chr(10); is a string containing "\r\n", a CRLF (newline) as the variable name states.
		 */

		return preg_replace('/\r\n?/', "\n", $string);
	}

	/**
	 * Remove any html
	 *
	 * @param string $string
	 * @return string
	 */
	protected function removeHtmlLeftovers($string) {
		return strip_tags($string);
	}

	/**
	 * Filters a crawler for the given $filter (if available) and returns the text() if available. Otherwise it returns $default.
	 *
	 * @param Crawler $crawler
	 * @param string  $filter
	 * @param mixed   $default = ''
	 * @return mixed
	 */
	protected function filterTextOrDefault(Crawler $crawler, $filter = NULL, $default = '') {
		$result = $default;

		if (!empty($filter)) {
			$filterResponse = $crawler->filter($filter);

			if ($filterResponse->count() >= 1) {
				$result = $filterResponse->text();
			}
		}

		return $result;
	}

}