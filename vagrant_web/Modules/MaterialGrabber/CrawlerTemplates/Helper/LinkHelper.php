<?php

namespace Modules\MaterialGrabber\CrawlerTemplates\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Modules\MaterialGrabber\CrawlerTemplates\Interfaces\CrawlerInterface;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\Entities\Material;
use Modules\MaterialGrabber\Entities\Stichwort;
use Modules\MaterialGrabber\Services\LinkManager;
use StevenBuehner\BibleVerseBundle\Entity\BibleVerse;
use StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This file was created by  steven
 * Created: 03.07.16 22:45
 * All Rights reserved. No usage without written permission allowed.
 */
class LinkHelper {

	const KEY_KEYWORD        = '_stw';
	const KEY_BIBLEVERSE     = '_bible';
	const KEY_HEFT           = '_heft';
	const KEY_POOL           = '_pool';
	const KEY_TITLE          = '_title';
	const KEY_DESCRIPTION    = '_desc';
	const KEY_AUTHOR         = '_author';
	const KEY_CRAWLER_CLASS  = '_crawlClass';
	const KEY_CRAWLER_DI_KEY = '_crawlDI';
	/** @var LinkManager $linkManager */
	protected $linkManager;
	/** @var EntityManager */
	protected $entityManager;
	/** @var ContainerInterface */
	protected $container;

	/**
	 * LinkHelper constructor.
	 *
	 * @param LinkManager        $linkManager
	 * @param EntityManager      $entityManager
	 * @param ContainerInterface $container
	 */
	public function __construct(LinkManager $linkManager, EntityManager $entityManager, ContainerInterface $container) {
		$this->linkManager   = $linkManager;
		$this->entityManager = $entityManager;
		$this->container     = $container;
	}

	/**
	 * @param Link $link
	 * @return bool
	 */
	public function hasCrawlerClass(Link $link) {
		return $this->hasOptionField($link, self::KEY_CRAWLER_CLASS);
	}

	/**
	 * @param Link   $link
	 * @param string $fieldname
	 * @return bool
	 */
	public function hasOptionField(Link $link, $fieldname) {
		$options = $link->getOptions();

		return (isset($options[$fieldname]));
	}

	/**
	 * @param Link $link
	 * @return bool
	 */
	public function hasCrawlerDIKey(Link $link) {
		return $this->hasOptionField($link, self::KEY_CRAWLER_DI_KEY);
	}

	/**
	 * @param Link $link
	 * @return false|CrawlerInterface
	 */
	public function getCrawlerInstance(Link $link) {
		$result = $this->getCrawlerDIKeyCrawlerInstance($link);

		if ($result === FALSE) {
			$result = $this->getCrawlerClassInstance($link);
		}

		return $result;
	}

	/**
	 * @param Link $link
	 * @return bool|CrawlerInterface
	 */
	public function getCrawlerDIKeyCrawlerInstance(Link $link) {
		$crawlerDiKey = $this->getCrawlerDIKey($link);

		if ($crawlerDiKey === FALSE) {
			return FALSE;
		}

		return $this->container->get($crawlerDiKey);
	}

	/**
	 * @param Link $link
	 * @return string|false
	 */
	public function getCrawlerDIKey(Link $link) {
		return $this->getOptionField($link, self::KEY_CRAWLER_DI_KEY, FALSE);
	}

	/**
	 * @param Link   $link
	 * @param string $fieldname
	 * @param mixed  $defaultValue
	 * @return mixed
	 */
	public function getOptionField(Link $link, $fieldname, $defaultValue = NULL) {
		$options = $link->getOptions();

		return (isset($options[$fieldname])) ? $options[$fieldname] : $defaultValue;
	}

	/**
	 * @param Link $link
	 * @return false|CrawlerInterface
	 */
	public function getCrawlerClassInstance(Link $link) {
		$crawlerClass = $this->getCrawlerClass($link);

		if ($crawlerClass === FALSE) {
			return FALSE;
		}

		return new $crawlerClass($this->container);
	}

	/**
	 * @param Link $link
	 * @return string|false
	 */
	public function getCrawlerClass(Link $link) {
		return $this->getOptionField($link, self::KEY_CRAWLER_CLASS, FALSE);
	}

	/**
	 * Remove all CrawlerClass and CrawlerDi Information if existing
	 *
	 * @param Link $link
	 */
	public function resetCrawler(Link $link) {
		$this->removeOptionField($link, self::KEY_CRAWLER_CLASS);
		$this->removeOptionField($link, self::KEY_CRAWLER_DI_KEY);
	}

	/**
	 * @param Link   $link
	 * @param string $fieldname
	 */
	public function removeOptionField(Link $link, $fieldname) {
		$options = $link->getOptions();

		if (isset($options[$fieldname])) {
			unset($options[$fieldname]);
			$link->setOptions($options);
		}
	}

	/**
	 * @param Link   $link
	 * @param string $authorWert
	 */
	public function setAuthor(Link $link, $authorWert) {
		$this->setOptionField($link, self::KEY_AUTHOR, $authorWert);
	}

	/**
	 * @param Link   $link
	 * @param string $fieldname
	 * @param mixed  $value
	 * @param bool   $cleanup
	 */
	public function setOptionField(Link $link, $fieldname, $value, $cleanup = FALSE) {
		$options = $link->getOptions();

		if (TRUE === $cleanup && is_string($fieldname)) {
			$fieldname = preg_replace('~([,:;-]|\s)+$~', '', $fieldname);
			$fieldname = preg_replace('~^([,:;-]|\s)+~', '', $fieldname);
		}

		$options[$fieldname] = $value;

		$link->setOptions($options);
	}

	/**
	 * @param Link   $link
	 * @param string $descriptionWert
	 */
	public function setDescription(Link $link, $descriptionWert) {
		$this->setOptionField($link, self::KEY_DESCRIPTION, $descriptionWert);
	}

	/**
	 * @param Link   $link
	 * @param string $titleWert
	 */
	public function setTitle(Link $link, $titleWert) {
		$this->setOptionField($link, self::KEY_TITLE, $titleWert);
	}

	/**
	 * @param Link   $link
	 * @param string $poolWert
	 */
	public function setPool(Link $link, $poolWert) {
		$this->setOptionField($link, self::KEY_POOL, $poolWert);
	}

	/**
	 * @param Link   $link
	 * @param string $heftWert
	 */
	public function setHeft(Link $link, $heftWert) {
		$this->setOptionField($link, self::KEY_HEFT, $heftWert);
	}

	/**
	 * @param Link   $link
	 * @param string $bibelverse
	 */
	public function addBibelverse(Link $link, $bibelverse) {
		$bvJar       = $this->getOptionField($link, self::KEY_BIBLEVERSE, []);
		$key         = strtolower(trim($bibelverse));
		$key         = preg_replace('~\s*~', '', $key);
		$value       = trim($bibelverse);
		$bvJar[$key] = $value;
		$this->setOptionField($link, self::KEY_BIBLEVERSE, $bvJar, $cleanup = FALSE);
	}

	/**
	 * Resets the Bibleverse Storage
	 *
	 * @param Link $link
	 */
	public function clearBibleverse(Link $link) {
		$this->setOptionField($link, self::KEY_BIBLEVERSE, []);
	}

	/**
	 * @param Link   $link
	 * @param string $stichwort
	 */
	public function addStichwort(Link $link, $stichwort) {
		$stwJar       = $this->getOptionField($link, self::KEY_KEYWORD, []);
		$key          = strtolower(trim($stichwort));
		$value        = trim($stichwort);
		$stwJar[$key] = $value;
		$this->setOptionField($link, self::KEY_KEYWORD, $stwJar, $cleanup = FALSE);
	}

	/**
	 * Resets the Stichwort Storage
	 *
	 * @param Link $link
	 */
	public function clearStichworte(Link $link) {
		$this->setOptionField($link, self::KEY_KEYWORD, []);
	}

	public function clearLinkFromAnyMaterialAssociations(Link $link) {
		$repo = $this->entityManager->getRepository('LinkBundle:Material');
		$repo->removeMaterialAndAssociations($link);
	}


	public function createMaterialFromLinkMeta(Link $link) {
		$material = $link->getMaterial();

		if ($material === NULL) {
			$material = new Material();
			$material->setLink($link);
		}

		// Beide persisten (!)
		// Falls es beim ersten nur ein Proxy war
		$this->entityManager->persist($material);

		$material->setHeft($this->getHeft($link));
		$material->setPool($this->getPool($link));
		$material->setTitel($this->getTitle($link));
		$material->setBeschreibung($this->getDescription($link));
		$material->setAutoren($this->getAuthor($link));


		// Gleiche Stichwort-Collections ab
		$repo        = $this->entityManager->getRepository('LinkBundle:Stichwort');
		$stws        = $this->getStichwoerter($link);
		$resolvedStw = new ArrayCollection();

		foreach ($stws as $s) {
			$contains = FALSE;
			/** @var Stichwort $mS */
			foreach ($material->getStichwort() as $mS) {
				if ($mS->getText() == $s) {
					$contains = TRUE;
					$resolvedStw->add($mS);
					break;
				}
			}

			if ($contains === FALSE) {
				// Függe fehlende Stichworte hinzu
				$stichwort = $repo->getStichwortFromText($s);
				$resolvedStw->add($stichwort);
				$material->addStichwort($stichwort);
			}
		}

		// Entferne Stichwörter, die zu viel sind
		foreach ($material->getStichwort() as $mS) {
			if (!$resolvedStw->contains($mS)) {
				$material->removeStichwort($mS);
			}
		}


		// Gleiche Bibelstellen-Collections ab
		$repo       = $this->entityManager->getRepository('LinkBundle:Bibelstelle');
		$helper     = $this->container->get('bible_verse.helper');
		$bvStrings  = $this->getBibleverses($link);
		$bvObjects  = [];
		$resolvedBS = new ArrayCollection();

		// Erkenne alle Bibelverse in angegebenen Strings
		foreach ($bvStrings as $bvString) {
			$bvs       = $helper->stringToBibleVerse($bvString);
			$bvObjects = array_merge($bvObjects, $bvs);
		}

		/** @var BibleVerse $b */
		foreach ($bvObjects as $b) {

			$contains = FALSE;

			/** @var BibleVerseInterface $mB */
			foreach ($material->getBibelstelle() as $mB) {
				if ($mB->getBookId() == $b->getBookId() &&
					$mB->getFromChapter() == $b->getFromChapter() &&
					$mB->getFromVerse() == $b->getFromVerse() &&
					$mB->getToChapter() == $b->getToChapter() &&
					$mB->getToVerse() == $b->getToVerse()
				) {
					$contains = TRUE;
					$resolvedBS->add($mB);
					break;
				}
			}

			if ($contains === FALSE) {
				// Függe fehlende Bibelstellen hinzu
				$bibelstelle = $repo->getBibelstelle($b);
				$resolvedBS->add($bibelstelle);
				$material->addBibelstelle($bibelstelle);
			}
		}

		// Entferne Bibelstellen, die zu viel sind
		foreach ($material->getBibelstelle() as $mB) {
			if (!$resolvedBS->contains($mB)) {
				$material->removeBibelstelle($mB);
			}
		}


		$this->entityManager->flush($material);
		$this->entityManager->flush($link);


		return $material;
	}

	/**
	 * @param Link   $link
	 * @param string $default
	 * @return string
	 */
	public function getHeft(Link $link, $default = '') {
		return $this->getOptionField($link, self::KEY_HEFT, $default);
	}

	/**
	 * @param Link   $link
	 * @param string $default
	 * @return string
	 */
	public function getPool(Link $link, $default = '') {
		return $this->getOptionField($link, self::KEY_POOL, $default);
	}

	/**
	 * @param Link   $link
	 * @param string $default
	 * @return string
	 */
	public function getTitle(Link $link, $default = '') {
		return $this->getOptionField($link, self::KEY_TITLE, $default);
	}

	/**
	 * @param Link   $link
	 * @param string $default
	 * @return string
	 */
	public function getDescription(Link $link, $default = '') {
		return $this->getOptionField($link, self::KEY_DESCRIPTION, $default);
	}

	/**
	 * @param Link   $link
	 * @param string $default
	 * @return string
	 */
	public function getAuthor(Link $link, $default = '') {
		return $this->getOptionField($link, self::KEY_AUTHOR, $default);
	}

	/**
	 * @param Link $link
	 * @return string[]
	 */
	public function getStichwoerter(Link $link) {
		return $this->getOptionField($link, self::KEY_KEYWORD, []);
	}

	/**
	 * @param Link $link
	 * @return string[]
	 */
	public function getBibleverses(Link $link) {
		return $this->getOptionField($link, self::KEY_BIBLEVERSE, []);
	}

	/**
	 * Copies all data from the $sourceLink to $target Link BUT diKey and diClass
	 *
	 * @param Link $sourceLink
	 * @param Link $targetLink
	 */
	public function copyMetaDataFromLinkToLink(Link $sourceLink, Link $targetLink) {
		$newOptions = array_merge($sourceLink->getOptions(), $targetLink->getOptions());

		if ($this->getCrawlerDIKey($targetLink) !== $this->getCrawlerDIKey($sourceLink)) {
			// Preserve old value
			if ($this->getCrawlerDIKey($targetLink) === FALSE && isset($newOptions[self::KEY_CRAWLER_DI_KEY])) {
				unset($newOptions[self::KEY_CRAWLER_DI_KEY]);
			} else {
				$newOptions[self::KEY_CRAWLER_DI_KEY] = $this->getCrawlerDIKey($targetLink);
			}

		}

		if ($this->getCrawlerClass($targetLink) !== $this->getCrawlerClass($sourceLink)) {
			// Preserve old value
			$newOptions[self::KEY_CRAWLER_CLASS] = $this->getCrawlerClass($targetLink);
		}

		$targetLink->setOptions($newOptions);
	}

	/**
	 * @param Link   $link
	 * @param string $crawlerDIKeyWert
	 */
	public function setCrawlerDIKey(Link $link, $crawlerDIKeyWert) {
		$this->setOptionField($link, self::KEY_CRAWLER_DI_KEY, $crawlerDIKeyWert);
	}

	/**
	 * @param Link   $link
	 * @param string $crawlerClassWert
	 */
	public function setCrawlerClass(Link $link, $crawlerClassWert) {
		$this->setOptionField($link, self::KEY_CRAWLER_CLASS, $crawlerClassWert);
	}


}