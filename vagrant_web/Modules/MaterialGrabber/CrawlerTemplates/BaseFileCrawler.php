<?php
/**
 * This file was created by  steven
 * Created: 03.07.16 21:29
 * All Rights reserved. No usage without written permission allowed.
 */

namespace Modules\MaterialGrabber\CrawlerTemplates;

use Curl\Curl;
use Goutte\Client;
use Modules\MaterialGrabber\CrawlerTemplates\Exceptions\FileNotDownloadableException;
use Modules\MaterialGrabber\CrawlerTemplates\Exceptions\WrongIndexTypeException;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Symfony\Component\BrowserKit\Request;

abstract class BaseFileCrawler extends BaseCrawler {

	/** @var bool */
	protected $fileNameMeyBeAutomaticallyChanged = FALSE;

	/** @var int[] */
	protected $expectedStatusCodes = [200];

	/** @var  AbstractGrabber */
	protected $grabber;

	/**
	 * @param Link            $link
	 * @param Client          $client
	 * @param AbstractGrabber $grabber
	 * @param bool            $onlyCrawlWhenCacheHasChanged
	 * @return bool - Success (true) / Failure (false)
	 */
	public function fetch(Link $link, Client $client, AbstractGrabber $grabber, $onlyCrawlWhenCacheHasChanged) {
		// Store grabber for subproccess (when need to get confvalues)
		$this->grabber = $grabber;
		$success       = TRUE;

		if ($this->preProcessing($link, $client) === FALSE) {
			return FALSE;
		}

		if ($link->isIndex()) {
			throw new WrongIndexTypeException('Expected Link to be type FILE. Type INDEX given');
		}
		// Delete file when
		// 1) Always Crawl! => Force => delete File
		// 2) File is empty => delete file
		if ($onlyCrawlWhenCacheHasChanged === FALSE && file_exists($link->getFilePath()) ||
			file_exists($link->getFilePath()) && is_file($link->getFilePath()) && filesize($link->getFilePath()) == 0
		) {
			unlink($link->getFilePath());
			$link->setFilePath(NULL);
		}


		$targetPath = $this->getTargetFilename($link, $client);

		$this->createDirIfNotExistant($targetPath);


		// Move the filename to the new Target-Filename (to ignore another download and to prevent multiple dead files), when
		// 1) Filename is not allowed to automatically change AND
		// 2) The acuired $targetPath is not identical with the path stored in the $link AND
		// 3) The file exists ins $link->filePath
		if (!$this->getFileNameMeyBeAutomaticallyChanged() &&
			!empty($link->getFilePath()) && $targetPath != $link->getFilePath() &&
			file_exists($link->getFilePath())
		) {
			$oldFilename = $link->getFilePath();
			$oldDir      = dirname($oldFilename);

			rename($oldFilename, $targetPath);

			$link->setFilePath($targetPath);
			$this->deleteFolderIfEmpty($oldDir);
		}


		// Download the file when
		// 1) $onlyCrawlWhenCacheHasChanged === false => force OR
		// 2) the acuired $targetPath is not identical with the path stored in the $link OR
		// 3) the files does not exist in $targetPath
		if ($onlyCrawlWhenCacheHasChanged === FALSE || $targetPath != $link->getFilePath() || !file_exists($targetPath)) {
			$success = $this->doSessionAwareDownload($link, $client, $targetPath,
													 $this->getFileNameMeyBeAutomaticallyChanged());
		}

		if (file_exists($link->getFilePath()) && is_file($link->getFilePath())) {
			$link->setMd5Cache(md5_file($link->getFilePath()));
		} else {
			$link->setMd5Cache(NULL);
			$this->linkMetaHelper->clearLinkFromAnyMaterialAssociations($link);
			throw new FileNotDownloadableException('File does not exist after download-attempt. Url: ' . $link->getUrl() . " (ID: {$link->getId()}); Filename: {$link->getFilePath()}");
		}

		$this->getEntityManager()->flush($link);

		return $this->postProcessing($link, $client, $success);

	}


	/**
	 * This will run before anything else ist done.
	 *
	 * @param Link   $link
	 * @param Client $client
	 * @result bool cancel (false) / continue (true)
	 */
	abstract protected function preProcessing(Link $link, Client $client);

	/**
	 * Get the absolute FilePath to store the file to.
	 *
	 * $this->linkManager->getDownloadFilePath(); can be used to get the Base folder of all Downloads.
	 *
	 * If a default GrabberConf is used a function could look like this:
	 * $this->linkManager->getDownloadFilePath() . DIRECTORY_SEPARATOR . $this->grabber->getGrabberConf()->getStoragePath() . DIRECTORY_SEPARATOR . 'WHATEVER SUBPATH';
	 *
	 * @param Link   $link
	 * @param Client $client
	 * @return string
	 */
	abstract protected function getTargetFilename(Link $link, Client $client);

	/**
	 * @return bool
	 */
	public function getFileNameMeyBeAutomaticallyChanged() {
		return $this->fileNameMeyBeAutomaticallyChanged;
	}

	/**
	 * @param bool $fileNameMeyBeAutomaticallyChanged
	 */
	public function setFileNameMeyBeAutomaticallyChanged($fileNameMeyBeAutomaticallyChanged) {
		$this->fileNameMeyBeAutomaticallyChanged = $fileNameMeyBeAutomaticallyChanged;
	}

	/**
	 * Remove the given directory, if it is empty
	 *
	 * @param string $folderPathToCheck MUST NOT end with '/'
	 */
	protected function deleteFolderIfEmpty($folderPathToCheck) {
		if (!empty($folderPathToCheck) && file_exists($folderPathToCheck) && is_dir($folderPathToCheck)) {
			if (count(glob($folderPathToCheck . DIRECTORY_SEPARATOR . '*')) === 0) {
				@rmdir($folderPathToCheck);
			}
		}
	}

	/**
	 *
	 * Takes the $link URL and downloads it with the session (cookies and referer) of the Client.
	 * The file will be stored to the absoulte $targetPath.
	 * If $fileNameMeyBeAutomaticallyChanged is set to True, the path where the file ist storged stays the same, but
	 * the filename is changed according to whatever the server sends as filename.
	 * The Link object will be updated to the finaly filePath.
	 * The Link object will be updated with the correct fileMD5 or NULL on error
	 * The Client will not be changed.
	 *
	 * @param Link   $link
	 * @param Client $client
	 * @param        $targetPath
	 * @param bool   $fileNameMeyBeAutomaticallyChanged
	 * @return bool success (true) / failure (false)
	 */
	protected function doSessionAwareDownload(Link $link, Client $client, $targetPath, $fileNameMeyBeAutomaticallyChanged = FALSE) {
		$curl = new Curl();

		// Copy cookies from client
		$cookieJar = $client->getCookieJar();
		$cookies   = $cookieJar->allValues($link->getUrl());

		foreach ($cookies as $id => $value) {
			$curl->setCookie($id, $value);
		}

		// Copy referer from client
		/** @var Request $request */
		$request = $client->getRequest();
		if ($request) {
			$curl->setReferrer($request->getUri());
		}

		// Todo: SetUserAgent

		// Set Temp-Filename
		$_tempBufferFilePath = tempnam(sys_get_temp_dir(), 'file-');
		$_tempBufferFileRes  = fopen($_tempBufferFilePath, 'w+');
		$curl->setOpt(CURLOPT_FILE, $_tempBufferFileRes);

		// Set path to store Header-Information
		$_headerBufferFilePath = tempnam(sys_get_temp_dir(), 'header-');
		$_headerBufferRes      = fopen($_headerBufferFilePath, 'w+');
		$curl->setOpt(CURLOPT_WRITEHEADER, $_headerBufferRes);
		$curl->setOpt(CURLOPT_HEADER, FALSE); // Gib keine Header-Infos im Bild aus (bisher Standard-Einstellung)


		// Execute Download
		$curl->get($link->getUrl());
		$httpStatusCode   = $curl->http_status_code;
		$httpError        = $curl->http_error;
		$httpErrorMessage = $curl->http_error_message;
		$httpErrorCode    = $curl->http_status_code;
		$responseHeaders  = $curl->response_headers;

		rewind($_headerBufferRes);
		$headers = stream_get_contents($_headerBufferRes);

		// Close CURL
		$curl->close();
		fclose($_tempBufferFileRes);
		fclose($_headerBufferRes);
		unlink($_headerBufferFilePath);

		if (!in_array($httpStatusCode, $this->getExpectedStatusCodes())) {
			return FALSE;
		}

		if ($fileNameMeyBeAutomaticallyChanged === TRUE) {
			if (preg_match('~^[Cc]ontent-[Dd]isposition: .*filename=[\'"]?([^\'"]+)[\'"]?~im', $headers,
						   $matches) == 1
			) {
				$targetPath = dirname($targetPath) . DIRECTORY_SEPARATOR . $matches[1];
			}
		}

		// Delete existing files, if they differ from the freshly downloaded one
		if (file_exists($targetPath) && is_file($targetPath)) {
			$oldMd5 = md5_file($targetPath);
			$newMd5 = md5_file($_tempBufferFilePath);

			if ($oldMd5 != $newMd5) {
				// Remove existing file (files are NOT equal)
				unlink($targetPath);
				rename($_tempBufferFilePath, $targetPath);
			} else {
				// Remove downloaded file (files ARE equal)
				unlink($_tempBufferFilePath);
			}
		} else {
			rename($_tempBufferFilePath, $targetPath);
		}

		$link->setFilePath($targetPath);

		return TRUE;
	}

	/**
	 * @return array
	 */
	public function getExpectedStatusCodes() {
		return $this->expectedStatusCodes;
	}

	/**
	 * @param array $expectedStatusCodes
	 */
	public function setExpectedStatusCodes($expectedStatusCodes) {
		$this->expectedStatusCodes = $expectedStatusCodes;
	}

	/**
	 * This will done after the file is downloaded.
	 *
	 * @param Link   $link
	 * @param Client $client
	 * @param bool   $downloadSuccess
	 * @return bool Crawl was a success (true) / Crawl failed (false)
	 */
	abstract protected function postProcessing(Link $link, Client $client, $downloadSuccess);

}