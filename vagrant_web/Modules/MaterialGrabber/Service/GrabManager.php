<?php

namespace Modules\MaterialGrabber\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use Modules\MaterialGrabber\CrawlerTemplates\Exceptions\FileNotDownloadableException;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\Entities\Material;
use Modules\MaterialGrabber\Entities\Stichwort;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\Repositories\LinkRepository;
use Modules\MaterialGrabber\Repositories\MaterialRepository;
use StevenBuehner\BibleVerseBundle\Entity\BibleVerse;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

class GrabManager {

	/* @var $entityManager \Modules\MaterialGrabber\Repositories\LinkRepository */
	protected $entityManager;

	/** @var Container */
	protected $container;

	protected $materialFilesPath;

	public function __construct(EntityManager $entityManager, Container $container, $materialPath) {
		$this->entityManager     = $entityManager;
		$this->container         = $container;
		$this->materialFilesPath = $materialPath;
	}

	public function runNeedsGrabbing(AbstractGrabber $grabber, ProgressBar $progressBar, $forceGrabbing = FALSE) {
		$countSum = $this->getLinkRepository()->countByGrabberIDs($grabber->getGrabberConf()->getId());
		$page     = 0;

		if ($countSum > 0) {
			$progressBar->start($countSum);
		}

		while ($allLinks = $this->getAllGrabberLinks($grabber, $page++)) {
			foreach ($allLinks as $link) {
				try {
					if ($grabber->needsGrabbing($link, $forceGrabbing)) {
						$link->setStatus(Link::$STATUS_WAITING);
					}
				} catch (\Exception $e) {
					$link->setStatus(Link::$STATUS_FINSIHED_WITH_ERRORS);
					var_dump($e->getMessage());
				}

				$progressBar->advance();
			}

			$this->entityManager->flush();

			// Alle Link-Entities aus dem EntityManager entfernen
			$this->entityManager->getUnitOfWork()->clear(Link::class);
		}

		// Can not be used -> because it would also clear the grabbers used in the CMD
		// $this->entityManager->clear();

		if ($countSum > 0) {
			$progressBar->finish();
		}
	}

	/**
	 * @return LinkRepository
	 */
	protected function getLinkRepository() {
		return $this->entityManager->getRepository("LinkBundle:Link");
	}

	/**
	 * @param AbstractGrabber $grabber
	 * @return Link[]
	 */
	public function getAllGrabberLinks(AbstractGrabber $grabber, $page = 0, $limit = 1000) {
		return $this->getLinkRepository()->findBy(
			['grabber' => $grabber->getGrabberConf()->getGrabberConfig()],
			$order = ['priority' => 'DESC', 'id' => 'ASC'],
			$limit,
			$offset = $page * $limit
		);
	}

	public function runGrabLinks(AbstractGrabber $grabber, ProgressBar $progressBar, $forceGrabbing = FALSE) {

		while ($allLinks = $this->getWaitingLinks($grabber, $page = 0, $limit = 200)) {
			$maxCount        = $this->countAllGrabberLinks($grabber);
			$countAllWaiting = $this->countGrabbableLinks($grabber);

			if ($maxCount > 0) {
				$progressBar->start($maxCount);
				$progressBar->setProgress($maxCount - $countAllWaiting);
			}

			/** @var Link $link */
			foreach ($allLinks as $link) {
				$tryRun = 1;

				try {
					// $onlyCrawlWhenCacheHasChanged = false, because "needsGrabbing" did already the checking
					// Todo: Optional -> check if force was activated here ...

					// If you need to perform multiple merges of entities that share certain subparts of their
					// object-graphs and cascade merge, then you have to call EntityManager#clear() between the
					// successive calls to EntityManager#merge(). Otherwise you might end up with multiple
					// copies of the “same” object in the database, however with different ids.
					// $this->entityManager->clear();
					// $link = $this->entityManager->merge($link);

					$grabber->grabLink($link, !$forceGrabbing);
					$link->setStatus(Link::$STATUS_FINISHED_SUCCESSFULL);
				} catch (ConnectException $e) {
					// Try again on conection errors like Timeout
					$tryRun++;

					if ($tryRun > 3) {
						$this->cleanUpLinkAfterError($link, $e);
						throw $e;
					}
				} catch (TooManyRedirectsException $e) {
					$this->cleanUpLinkAfterError($link, $e);
				} catch (FileNotDownloadableException $e) {
					$this->cleanUpLinkAfterError($link, $e);
				} catch (\Exception $e) {
					try {
						/** @var MaterialRepository $matRepo */
						$matRepo = $this->entityManager->getRepository('LinkBundle:Material');
						$matRepo->removeMaterialAndAssociations($link);
					} catch (ORMException $e2) {
						// ORM is closed
					}


					if ($this->entityManager->isOpen()) {
						$this->cleanUpLinkAfterError($link, $e);
					} else {
						// And error accured where the link-manager was closed -> everything else would be useless
						throw $e;
					}
				}

				$this->entityManager->flush();
				// $this->entityManager->detach($link);
				$progressBar->advance();
			}

			// Diese Entities aus dem EntityManager entfernen (clear memory)
			$this->entityManager->getUnitOfWork()->clear(Link::class);
			$this->entityManager->getUnitOfWork()->clear(Material::class);
			$this->entityManager->getUnitOfWork()->clear(Stichwort::class);
			$this->entityManager->getUnitOfWork()->clear(BibleVerse::class);
		}

		$allLinks        = NULL;
		$maxCount        = $this->countAllGrabberLinks($grabber);
		$countAllWaiting = $this->countGrabbableLinks($grabber);
		$progressBar->start($maxCount);
		$progressBar->setProgress($maxCount - $countAllWaiting);
		$progressBar->finish();
	}

	/**
	 * @param AbstractGrabber $grabber
	 * @param int             $limit
	 * @return Link[]
	 */
	public function getWaitingLinks(AbstractGrabber $grabber, $page = 0, $limit = 1000) {
		return $this->getLinkRepository()->findBy(
			[
				'grabber' => $grabber->getGrabberConf()->getGrabberConfig(),
				'status'  => Link::$STATUS_WAITING
			],
			$order = ['priority' => 'DESC', 'lastUpdate' => 'ASC'],
			$limit
		);
	}

	protected function countAllGrabberLinks(AbstractGrabber $grabber) {
		return $this->getLinkRepository()->countByGrabberIDs($grabber->getGrabberConf()->getId());
	}

	protected function countGrabbableLinks(AbstractGrabber $grabber) {
		return $this->getLinkRepository()->countByGrabberIDsAndStatus($grabber->getGrabberConf()->getId(),
																	  Link::$STATUS_WAITING);
	}

	/**
	 * @param Link       $link
	 * @param \Exception $e
	 * @return Link
	 * @throws ORMException (The Entity Manager is closed.)
	 */
	protected function cleanUpLinkAfterError(Link $link, \Exception $e) {
		$link->setStatus(Link::$STATUS_FINSIHED_WITH_ERRORS);
		$link->setMd5Cache(NULL);

		$path = $link->getFilePath();
		if (!empty($path) && file_exists($path)) {
			unlink($path);
		}

		$link->setFilePath(NULL);
		$this->entityManager->flush($link);

		return $link;
	}

	/**
	 * @param AbstractGrabber[] $grabber
	 * @param                   $output
	 */
	public function displayFailedLinks($grabber, OutputInterface $output) {
		$failedLinks = $this->getFailedLinks($grabber);
		$table       = new Table($output);
		$table->setHeaders(['ID', 'Grabber', 'URLs', 'Type']);

		foreach ($failedLinks as $link) {
			$id      = $link->getId();
			$grabber = $link->getGrabber()->getName();
			$url     = $link->getUrl();
			$type    = ($link->isIndex()) ? 'Index' : 'file';
			$table->addRow([$id, $grabber, $url, $type]);
		}

		$countLinksFailed = count($failedLinks);
		if ($countLinksFailed > 0) {
			$output->writeln("Following {$countLinksFailed} link(s) failed:");
			$table->render();
		} else {
			$output->writeln('No links failed. Great!');
		}
	}

	/**
	 * @param AbstractGrabber[] $grabber
	 * @return Link[]
	 */
	public function getFailedLinks($grabber) {
		$grabConfs = [];
		foreach ($grabber as $grab) {
			$grabConfs[] = $grab->getGrabberConf()->getGrabberConfig();
		}

		return $this->getLinkRepository()->findBy(
			[
				'grabber' => $grabConfs,
				'status'  => Link::$STATUS_FINSIHED_WITH_ERRORS
			],
			$order = ['priority' => 'DESC', 'id' => 'ASC']
		);
	}

}