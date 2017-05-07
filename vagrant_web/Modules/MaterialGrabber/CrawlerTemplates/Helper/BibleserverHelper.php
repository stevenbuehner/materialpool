<?php

namespace Modules\MaterialGrabber\CrawlerTemplates\Helper;

use Doctrine\ORM\EntityManager;
use Modules\MaterialGrabber\Services\LinkManager;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This file was created by  steven
 * Created: 03.07.16 22:45
 * All Rights reserved. No usage without written permission allowed.
 */
class BibleserverHelper {

	const BASE_URL_BIBELSTELLE = 'http://www.bibleserver.com/text';

	// http://www.bibleserver.com/text/LUT.ELB.EU/Johannes3,16
	const BASE_URL_SUCHE       = 'http://www.bibleserver.com/text/LUT.ELB.EU/Johannes3,16';
	protected $bibleVerseService;

	/**
	 * LinkHelper constructor.
	 *
	 * @param LinkManager        $linkManager
	 * @param EntityManager      $entityManager
	 * @param ContainerInterface $container
	 */
	public function __construct(BibleVerseService $bibleVerseService) {
		$this->bibleVerseService = $bibleVerseService;
	}

	public function getBibelstellenLink($bibelstelle, $uebersetzungen = ['ELB', 'LUT']) {
		$result = FALSE;

		if (class_implements(BibleVerseInterface::class, $bibelstelle)) {
			$result = $this->getBibleVerseService()->bibleVerseToString($bibelstelle, 'long', 'de');
		} else {
			$bibleVerses = $this->getBibleVerseService()->stringToBibleVerse($bibelstelle);
			if (count($bibleVerses) > 0) {
				$bibleVerse = array_shift($bibleVerses);
				$result     = $this->getBibleVerseService()->bibleVerseToString($bibleVerse);
			}
		}

		if ($result) {
			$result = self::BASE_URL_BIBELSTELLE . DIRECTORY_SEPARATOR . join('.',
																			  $uebersetzungen) . DIRECTORY_SEPARATOR . $result;
		}

		return $result;
	}

	/**
	 * @return BibleVerseService
	 */
	protected function getBibleVerseService() {
		return $this->bibleVerseService;
	}

	public function getBibeltextSucheLink($suchwort, $uebersetzungen = ['ELB', 'LUT']) {
		// Todo: Implement getBibeltextSucheLink
		throw new \Exception('Not implemented yet');
	}


}