<?php

namespace Modules\IdeaSpektrumBundle\Grabber;

use App\Models\Keyword;
use App\Models\Material;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use Goutte\Client;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabberConfig;
use Modules\MaterialGrabber\GrabberTemplates\Helper\FileHashHelper;
use Modules\MaterialGrabber\GrabberTemplates\Helper\SessionAwareClientDownload;
use Symfony\Component\DomCrawler\Crawler;

// putenv('HTTP_PROXY=http://localhost:8888');

/**
 * @property IdeaSpektrumGrabberConfig $grabberConf
 * @method IdeaSpektrumGrabberConfig getGrabberConf()
 */
class IdeaSpektrumGrabber extends AbstractGrabber {

	const BASE_URI = 'http://www.idea.de';

	protected $loggedIn = FALSE;
	protected $ausgaben = [];
	protected $container;


	public function __construct(AbstractGrabberConfig $grabberConf) {
		parent::__construct($grabberConf);

		$this->relativeResourcePath = 'grabber/IdeaSpektrum';
	}


	/**
	 * Returns true if all Configurations are falid.
	 * Returns false, if something need to be updated, before the Crawlers can run (i.e. username, password, tokens,
	 * ...)
	 *
	 * @return bool
	 * */
	public function isConfigValid() {
		$this->getAusgaben($this->client);

		return $this->login($this->client);
	}


	public function getAusgaben(Client $client) {
		// Cache
		if (count($this->ausgaben) != 0) {
			return $this->ausgaben;
		}

		// First log in
		if (!$this->isLoggedIn()) {
			$this->login($client);
		}

		// Gehe auf die Startseite um alle Ausgaben zu sammeln
		$crawler = $client->request('GET',
									self::BASE_URI . '/e-paper.html'
		);
		$jahre   = $crawler->filter('.epaperdownloadarchiv select[name=jahr] option')
						   ->each(function (Crawler $node, $i) {
							   return $node->attr('value');
						   });

		$ausgaben = [];
		foreach ($jahre as $jahr) {
			$crawler = $this->client->request(
				'GET',
				self::BASE_URI . "/?eID=meinidea&eID=meinidea&status=getAusgaben&jahr={$jahr}&land=1",
				[],
				[],
				['HTTP_Accept' => 'application/json, text/javascript, */*; q=0.01']
			);

			$dateiEndungen = $crawler->filter('option')->each(function (Crawler $node, $i) {
				$value = $node->attr('value');
				preg_match('~[0-9_-]+-[0-9]{4}~', $value, $match);

				return $match[0];
			});

			$ausgabenUrls = [];
			foreach ($dateiEndungen as $datei) {
				preg_match('~(.+)-([0-9]{4})~', $datei, $match);
				$aus                = $match[1];
				$j                  = $match[2];
				$ausgabenUrls[$aus] = self::BASE_URI . "/?eID=meinidea&eID=meinidea&status=downloadPDF&land=1&jahr={$j}&ausgabe={$datei}.pdf";
			}

			$ausgaben[$jahr] = $ausgabenUrls;
		}

		// http://www.idea.de/?eID=meinidea&eID=meinidea&status=downloadPDF&land=1&jahr='+jahr+'&ausgabe='+ausgabe+'
		$this->ausgaben = $ausgaben;

		return $ausgaben;
	}

	/**
	 * @return boolean
	 */
	public function isLoggedIn() {
		return $this->loggedIn;
	}

	/**
	 * @param boolean $loggedIn
	 */
	public function setLoggedIn($loggedIn) {
		$this->loggedIn = $loggedIn;
	}

	/**
	 * @param Client $client
	 * @return bool
	 */
	public function login(Client $client) {
		/** @var IdeaSpektrumGrabberConfig $conf */
		$conf = $this->getGrabberConf();

		$crawler = $client->request(
			'POST',
			self::BASE_URI . '/e-paper.html',
			['user'                       => $conf->getUsername(),
			 'pass'                       => $conf->getPasswort(),
			 'logintype'                  => 'login',
			 'pid'                        => '11',
			 'redirect_url'               => '',
			 'tx_felogin_pi1[noredirect]' => '0',
			 'submit'                     => 'Anmelden']
		);

		$inputField = $crawler->filter('.epaperdownload select[name=ausgabe]');
		$this->setLoggedIn($inputField->count() == 1);

		return $this->isLoggedIn();
	}

	/**
	 * This function is called, before any grabbing Requests are made.
	 * It can be used to initialize what ever
	 *
	 * @return void
	 */
	public function beforeGrabbing() {
		// Setup initial grabs
		$ausgabenAlle = $this->getAusgaben($this->client);

		// http://www.idea.de/?eID=meinidea&eID=meinidea&status=downloadPDF&land=1&jahr=2016&ausgabe=20-2016.pdf
		// http://www.idea.de/?eID=meinidea&eID=meinidea&status=downloadPDF&land=1&jahr=2016&ausgabe=31-32-2016.pdf
		foreach ($ausgabenAlle as $jahr => $ausgabenJahr) {

			foreach ($ausgabenJahr as $ausgabeNr => $url) {

				/** @var Link $link */
				$link = Link::firstOrNew(
					[
						'grabber_id' => $this->getGrabberConf()->getId(),
						'url'        => $url
					], [
						'status'   => Link::$STATUS_WAITING,
						'is_index' => FALSE
					]);

				$link->addOption('jahr', $jahr);
				$link->addOption('ausgabe', $ausgabeNr);

				$link->save();
			}

		}

	}

	/**
	 * This function is called, after all grabbing requests.
	 * It can be used to destroy any sessions or what ever.
	 *
	 * @return void
	 */
	public function afterGrabbing() {
		$this->client->getHistory()->clear();
	}

	/**
	 * This function is called before a entity will be grabbed. Usually all Links will first be evaluated and then they
	 * will be grabbed.
	 * Returns true, if the given Link-Entity needs to be grabbed.
	 * Returns false, if the given Link-Entity can be skipped.
	 *
	 * @param Link $link
	 * @param bool $force
	 * @return bool
	 */
	public function needsGrabbing(Link $link, $force = FALSE) {
		if ($link->is_index) {
			return TRUE;
		} else {
			return $link->resource_id === NULL;
		}
	}

	/**
	 *
	 * This function will be called to perform whatever grabbing action is needed for this link.
	 * For big grabber it is suggested to forward the actual request concrete Crawler.
	 *
	 * @param Link $link
	 * @param bool $onlyCrawlWhenCacheHasChanged
	 */
	public function grabLink(Link $link, $onlyCrawlWhenCacheHasChanged = TRUE) {

		/** @var Resource $assignedResource */
		$assignedResource = $link->resource;

		if ($assignedResource == NULL) {

			/** @var SessionAwareClientDownload $downloadHelper */
			$downloadHelper = resolve('grabber.sessiondownload');
			$tmpFile        = $downloadHelper->downloadFileWithSession($link->url, $this->client);

			if (!$tmpFile) {
				// TODO: ERROR happend - what to do with resource (to not destroy any relations
			} else {

				/** @var FileHashHelper $fileHashHelper */
				$fileHashHelper  = resolve(FileHashHelper::class);
				$link->md5_cache = $fileHashHelper->generateLocalFileHash($tmpFile);

				$year      = $link->getOption('jahr', 'no_year');
				$ausgabe   = $link->getOption('ausgabe', 'keine-ausgabe');
				$extension = 'pdf';
				$resource  = $this->moveTempFileToLinkResource($tmpFile, $link, $this->getGrabberConf()
																					 ->getStoragePath()
																	   . DIRECTORY_SEPARATOR . $year .
																	   DIRECTORY_SEPARATOR . $year . '-' . $ausgabe . '.' . $extension);

				$resource->remote_path = $link->url;
				$resource->save();

				$kw = Keyword::firstOrCreate(['title' => 'IdeaSpektrum']);

				$material        = new Material();
				$material->created_by = $this->createdByUserId;
				$material->modified_by = $this->createdByUserId;
				$material->from_bot = TRUE;
				$material->title = 'IdeaSpektrum ' .
					$link->getOption('jahr', '-') . '/' .
					$link->getOption('ausgabe', '-');
				$material->save();

				$material->keywords()->sync([$kw->id => ['relevance' => RelevanceInterface::RELEVANCE_USER_MAX]]);

				$link->material()->associate($material);

			}


		}


		// Todo: Implement $force / $onlyCrawlWhenCacheHasChanged = TRUE


	}
}