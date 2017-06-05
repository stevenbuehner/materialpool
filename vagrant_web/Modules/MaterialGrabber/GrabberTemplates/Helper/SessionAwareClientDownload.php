<?php
/**
 * Created by PhpStorm.
 * User: steven
 * Date: 30.06.16
 * Time: 22:28
 */

namespace Modules\MaterialGrabber\GrabberTemplates\Helper;


use Goutte\Client;
use GuzzleHttp\Exception\ClientException;

class SessionAwareClientDownload {

	public function downloadFileWithSession($url, Client $client) {

		$ext           = pathinfo($url, PATHINFO_EXTENSION);
		$_tempFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('grabber_dl_') . '.' . $ext;


		try {

			$client->request('GET', $url);

			file_put_contents($_tempFilePath, $client->getResponse()->getContent());
			$client->back();

		} catch (ClientException $e) {
			// Like 404 not found

			if (file_exists($_tempFilePath)) {
				unlink($_tempFilePath);
			}

			throw $e;
		} catch (\Exception $e) {
			if (file_exists($_tempFilePath)) {
				unlink($_tempFilePath);
			}

			throw $e;
		}


		return $_tempFilePath;
	}

}