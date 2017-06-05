<?php

namespace Modules\MaterialGrabber\Services;

use Modules\MaterialGrabber\Entities\GrabberConfig;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabberConfig;

class LinkManager {

	/**
	 * Add a link to the Database, if it does not have an equivalent link already (matching url and serviceClass only!)
	 *
	 * @param Link $link
	 * @return Link
	 */
	public function addLink(Link $link) {
		$result = Link::updateOrFirst($link->getAttributes());

		return $result;
	}

	/**
	 * @param GrabberConfig $grabberConf
	 * @param string        $url
	 * @param null|int      $priority
	 * @param null|int      $index
	 * @return bool
	 */
	public function hasLink(GrabberConfig $grabberConf, $url, $priority = NULL, $index = NULL) {
		$entity = $this->getLink($grabberConf, $url, $priority, $index);

		return $entity->exists;
	}

	/**
	 * @param GrabberConfig|AbstractGrabber|AbstractGrabberConfig $grabberConf
	 * @param string                                              $url
	 * @param null|int                                            $priority
	 * @param null|int                                            $index
	 * @return null|Link
	 * @throws \Exception
	 */
	public function getLink($grabberConf, $url, $priority = NULL, $index = NULL) {

		if ($grabberConf instanceof AbstractGrabber) {
			$grabberConf = $grabberConf->getGrabberConf()->getGrabberConfig();
		} else if ($grabberConf instanceof AbstractGrabberConfig) {
			$grabberConf = $grabberConf->getGrabberConfig();
		} else if (!$grabberConf instanceof GrabberConfig) {
			throw new \Exception('Invalid Type $grabberConf');
		}

		$query = $grabberConf->links()->where(['url' => $url]);

		if ($priority !== NULL) {
			$query->where(['priority' => $priority]);
		}

		if ($index !== NULL && ($index === TRUE || $index === FALSE)) {
			$query->where(['is_index' => $index]);
		}

		$found = $query->first();

		return $found;
	}

}