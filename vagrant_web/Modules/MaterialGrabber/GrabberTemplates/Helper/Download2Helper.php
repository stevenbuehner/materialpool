<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 30.06.16
 * Time: 22:28
 */

namespace Modules\MaterialGrabber\GrabberTemplates\Helper;


use Curl\Curl;
use Goutte\Client;
use Modules\MaterialGrabber\Entities\Link;
use Symfony\Component\BrowserKit\Request;

class Download2Helper {

	public function __construct() {
	}

	public function downloadFileWithSession(Link $link, $target, Client $client, $force = FALSE) {

		$this->createDirIfNotExistant($target);

		// Delete file if has is only 0 bytes
		if ($force || file_exists($link->getFilePath()) && is_file($link->getFilePath()) && filesize($link->getFilePath()) == 0) {
			unlink($link->getFilePath());
		}

		// Skip file, if it already exists
		if (!file_exists($link->getFilePath())) {

			$curl = new Curl();

			$cookieJar = $client->getCookieJar();
			$cookies   = $cookieJar->allValues($link->getUrl());

			foreach ($cookies as $id => $value) {
				$curl->setCookie($id, $value);
			}


			/** @var Request $request */
			$request = $client->getRequest();
			if ($request) {
				$curl->setReferrer($request->getUri());
			}

			$fileTarget = fopen($target, 'w');
			$curl->setOpt(CURLOPT_FILE, $fileTarget);
			$curl->setOpt(CURLOPT_HEADER, TRUE);
			// $curl->setOpt(CURLOPT_PROXY, 'http://127.0.0.1:8888');
			//  $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
			//  $curl->setOpt(CURLOPT_RETURNTRANSFER, 1);


			$curl->get($link->getUrl());
			//  var_dump($curl->error_message);
			//  var_dump($curl->response);


			$curl->close();
			fclose($fileTarget);

			if (file_exists($target)) {
				$link->setFilePath($target);
			}

			$curl = NULL;
		}
	}

	protected function createDirIfNotExistant($target) {
		// Create directory structure
		$dir = dirname($target);
		if (!empty($dir) && !file_exists($dir)) {
			mkdir($dir, 0777, $recursive = TRUE);
		}
	}
}