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
use Symfony\Component\BrowserKit\Request;

class SessionAwareCurlDownload {

	/** @var  Curl */
	protected $curl;

	public function __construct($curlOptions = []) {
		$this->setupCurl($curlOptions);

	}

	protected function setupCurl($options = []) {
		$this->curl = new Curl();

		// Set Default Options
		$this->curl->setOpt(CURLOPT_HEADER, FALSE);
		$this->curl->setOpt(CURLOPT_BINARYTRANSFER, TRUE);
		$this->curl->verbose(FALSE);

		// i.e.
		// $curl->setOpt(CURLOPT_PROXY, 'http://127.0.0.1:8888');
		$this->curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
		// $this->curl->setOpt(CURLOPT_RETURNTRANSFER, 1);

		foreach ($options as $key => $value) {
			$this->curl->setOpt($key, $value);
		}
	}


	public function downloadFileWithSession($url, Client $client) {

		$cookieJar = $client->getCookieJar();
		$cookies   = $cookieJar->allValues($url);

		foreach ($cookies as $id => $value) {
			$this->curl->setCookie($id, $value);
		}


		/** @var Request $request */
		$request = $client->getRequest();
		if ($request) {
			$this->curl->setReferer($request->getUri());
		}

		$ext         = pathinfo($url, PATHINFO_EXTENSION);
		$temp_file   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('grabber_dl_') . '.' . $ext;
		$temp_header = $temp_file . '_header';

		if (($fileTarget = fopen($temp_file, "w")) === FALSE) {
			throw new \Exception("fopen error for filename $temp_file");
		}

		if (($headerTarget = fopen($temp_header, "w")) === FALSE) {
			throw new \Exception("fopen error for filename $temp_header");
		}

		// $errorInfo = $temp_file . '_log';
		// $errorFile = fopen($errorInfo, 'w');
		// $this->curl->setOpt(CURLOPT_VERBOSE, TRUE);
		// $this->curl->setOpt(CURLOPT_STDERR, $errorFile);


		$this->curl->setOpt(CURLOPT_FILE, $fileTarget);
		$this->curl->setOpt(CURLOPT_WRITEHEADER, $headerTarget);

		$this->curl->get($url)->close();
		fclose($fileTarget);
		fclose($headerTarget);
		unlink($temp_header);
		// fclose($errorFile);
		// unlink($errorInfo);

		//  var_dump($curl->error_message);
		//  var_dump($curl->response);


		// TOdo: Rename tempfile to downloaded name?


		return ($this->curl->isError() || !file_exists($temp_file)) ? NULL : $temp_file;
	}
}