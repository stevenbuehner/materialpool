<?php

namespace Modules\MaterialGrabber\Services;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use Illuminate\Filesystem\FilesystemAdapter;
use Modules\MaterialGrabber\CrawlerTemplates\Exceptions\FileNotDownloadableException;
use Modules\MaterialGrabber\Entities\Link;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class GrabManager {

	public function __construct( ) {
	}

	public function runNeedsGrabbing(AbstractGrabber $grabber, ProgressBar $progressBar, $forceGrabbing = FALSE) {
		$countSum = $grabber->countLinks();

		if ($countSum > 0) {
			$progressBar->start($countSum);
		}

		$grabber->getGrabberConf()->getGrabberConfig()
				->links()
				->orderBy('priority', 'desc')
				->chunk(100, function ($links) use ($grabber, $forceGrabbing, $progressBar) {

					/** @var Link $link */
					foreach ($links as $link) {

						try {
							if ($grabber->needsGrabbing($link, $forceGrabbing)) {
								$link->status = Link::$STATUS_WAITING;
							}
						} catch (\Exception $e) {
							$link->status = Link::$STATUS_FINSIHED_WITH_ERRORS;
						}

						$progressBar->advance();
					}

				});

		if ($countSum > 0) {
			$progressBar->finish();
		}
	}

	public function runGrabLinks(AbstractGrabber $grabber, ProgressBar $progressBar, $forceGrabbing = FALSE) {

		$countAllWaiting = $grabber->countLinks(['status' => Link::$STATUS_WAITING]);

		if ($countAllWaiting > 0) {
			$progressBar->start($countAllWaiting);
		}

		$grabber->getGrabberConf()->getGrabberConfig()
				->links()
				->orderBy('priority', 'desc')
				->chunk(100, function ($links) use ($grabber, $forceGrabbing, $progressBar) {

					/** @var Link $link */
					foreach ($links as $link) {
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
							$link->status = Link::$STATUS_FINISHED_SUCCESSFULL;
						} catch (ConnectException $e) {
							// Try again on conection errors like Timeout
							$tryRun++;

							if ($tryRun > 3) {
								$this->cleanUpLinkAfterError($link);
								throw $e;
							}
						} catch (TooManyRedirectsException $e) {
							$this->cleanUpLinkAfterError($link);
						} catch (FileNotDownloadableException $e) {
							$this->cleanUpLinkAfterError($link);
						} catch (\Exception $e) {
							// TODO: Do some kind of cleanup
							$this->cleanUpLinkAfterError($link);

							throw $e;
						}

						$progressBar->advance();
					}

				});

		if ($countAllWaiting > 0) {
			$progressBar->finish();
		}

	}

	/**
	 * @param Link $link
	 * @return Link
	 */
	protected function cleanUpLinkAfterError(Link $link) {
		$link->status    = Link::$STATUS_FINSIHED_WITH_ERRORS;
		$link->md5_cache = NULL;

		$path = $link->file_path;
		if (!empty($path) && file_exists($path)) {
			unlink($path);
		}

		$link->file_path = NULL;
		$link->save();

		return $link;
	}


	/**
	 * @param AbstractGrabber[] $grabbers
	 * @param                   $output
	 */
	public function displayFailedLinks($grabbers, OutputInterface $output) {

		$grabberIds  = [];
		$grabberById = [];
		foreach ($grabbers as $g) {
			$id               = $g->getGrabberConf()->getId();
			$grabberIds[]     = $id;
			$grabberById[$id] = $g;
		}

		/** @var Link[] $failedLinks */
		$failedLinks = Link::whereIn('grabber_id', $grabberIds)
						   ->where('status', Link::$STATUS_FINSIHED_WITH_ERRORS);


		$table = new Table($output);
		$table->setHeaders(['ID', 'Grabber', 'URLs', 'Type']);

		foreach ($failedLinks as $link) {
			$id      = $link->id;
			$grabber = isset($grabberById[$link->grabber_id]) ? $grabberById[$link->grabber_id]->name : $link->grabber->name;
			$url     = $link->url;
			$type    = ($link->is_index) ? 'Index' : 'file';
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


}