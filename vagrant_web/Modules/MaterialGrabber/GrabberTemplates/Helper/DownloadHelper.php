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
use Modules\MaterialGrabber\Entities\Link;

class DownloadHelper {

	public function __construct() {
	}

	public function downloadFileWithSession(Link $link, Client $client) {

		$this->createDirIfNotExistant($link);
		$_tempFilePath = tempnam(sys_get_temp_dir(), 'file-');

		try {

			$client->request('GET', $link->getUrl());

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

		$this->decideAboutDownloadedFile($link, $_tempFilePath);

	}

	protected function createDirIfNotExistant(Link $link) {
		// Create directory structure
		$dir = dirname($link->getFilePath());
		if (!empty($dir) && !file_exists($dir)) {
			mkdir($dir, 0777, $recursive = TRUE);
		}
	}

	protected function decideAboutDownloadedFile(Link $link, $downloadedFilePath) {
		if (file_exists($downloadedFilePath) && filesize($downloadedFilePath) == 0) {
			unlink($downloadedFilePath);
			throw new \Exception('Filesize of downloaded file was 0');
		}

		// Compare downloaded file with existing one (if one exists)
		if (!empty($link->getFilePath()) && file_exists(($link->getFilePath())) && is_file($link->getFilePath())) {
			$oldMd5 = md5_file($link->getFilePath());
			$newMd5 = md5_file($downloadedFilePath);

			if ($oldMd5 != $newMd5) {
				// Cleanup old local file
				unlink($link->getFilePath());
				rename($downloadedFilePath, $link->getFilePath());
			} else {
				unlink($downloadedFilePath);
			}
		} else {
			rename($downloadedFilePath, $link->getFilePath());
		}
	}

	public function downloadBasicAuthFile(Link $link, Client $client, $username, $pasword) {
		$this->createDirIfNotExistant($link);

		$_tempFilePath = tempnam(sys_get_temp_dir(), 'file-');

		/*
		 * http://stackoverflow.com/questions/16939794/copy-remote-file-using-guzzle
		 */
		$client2 = $client->getClient();
		try {
			$client2->get($link->getUrl(), [
				'auth'    => [
					$username,
					$pasword
				],
				'save_to' => $_tempFilePath
			]);

		} catch (ClientException $e) {
			// Like 404 not found

			if (file_exists($_tempFilePath)) {
				unlink($_tempFilePath);
			}

			throw $e;
		}

		$this->decideAboutDownloadedFile($link, $_tempFilePath);

	}
}

