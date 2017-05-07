<?php

namespace Modules\MaterialGrabber\Services;

use Doctrine\ORM\EntityManager;
use Modules\MaterialGrabber\Entities\GrabberConf;
use Modules\MaterialGrabber\Entities\Link;
use Symfony\Component\DependencyInjection\Container;

class LinkManager {

	/* @var $entityManager \Modules\MaterialGrabber\Repositories\LinkRepository */
	protected $entityManager;

	/** @var Container */
	protected $container;

	protected $materialFilesPath;

	public function __construct(EntityManager $entityManager, Container $container, $materialPath) {
		$this->entityManager     = $entityManager;
		$this->container         = $container;
		$this->materialFilesPath = $materialPath;
	}

	/**
	 * Add a link to the Database, if it does not have an equivalent link already (matching url and serviceClass only!)
	 *
	 * @param Link $link
	 * @return Link
	 */
	public function addLink(Link $link) {
		$this->entityManager->persist($link);
		$this->entityManager->flush($link);

		return $link;
	}

	/**
	 * @param GrabberConf $grabberConf
	 * @param string      $url
	 * @param null|int    $priority
	 * @param null|int    $index
	 * @return bool
	 */
	public function hasLink(GrabberConf $grabberConf, $url, $priority = NULL, $index = NULL) {
		$found = $this->getLink($grabberConf, $url, $priority, $index);

		if ($found) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * @param GrabberConf $grabberConf
	 * @param string      $url
	 * @param null|int    $priority
	 * @param null|int    $index
	 * @return null|Link
	 */
	public function getLink(GrabberConf $grabberConf, $url, $priority = NULL, $index = NULL) {
		$criterias = ['grabber' => $grabberConf,
					  'url'     => $url];

		if ($priority !== NULL) {
			$criterias['priority'] = $priority;
		}

		if ($index !== NULL && ($index === TRUE || $index === FALSE)) {
			$criterias['isIndex'] = $index;
		}

		$found = $this->getLinkRepository()->findOneBy($criterias);

		return $found;
	}

	protected function getLinkRepository() {
		return $this->entityManager->getRepository('LinkBundle:Link');
	}

	public function getDownloadFilePath() {
		return $this->materialFilesPath;
	}


}