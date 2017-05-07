<?php

namespace Modules\MaterialGrabber\GrabberTemplates;

use Goutte\Client;

// putenv('HTTP_PROXY=http://localhost:8888');

/**
 * @property DefaultNoUserGrabberConfig $grabberConf
 * @method DefaultNoUserGrabberConfig getGrabberConf()
 */
abstract class DefaultNoLoginGrabber extends DefaultGrabber {


	/**
	 * Because no login is needed, this will always return true
	 *
	 * @param Client $client
	 * @return bool logged in
	 */
	public function login(Client $client) {
		return TRUE;
	}

	/**
	 * Returns true if all Configurations are valid.
	 * Returns false, if something need to be updated, before the Crawlers can run (i.e. username, password, tokens,
	 * ...)
	 * Returns always true, because no config is required
	 *
	 * @return bool
	 * */
	public function isConfigValid() {
		return TRUE;
	}

}