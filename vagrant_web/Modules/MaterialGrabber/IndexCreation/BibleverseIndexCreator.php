<?php
/**
 * This file was created by  steven
 * Created: 16.07.16 17:26
 * All Rights reserved. No usage without written permission allowed.
 */

namespace Modules\MaterialGrabber\IndexCreation;


use Doctrine\ORM\EntityManager;
use Modules\MaterialGrabber\Entities\Material;
use Modules\MaterialGrabber\Entities\Stichwort;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BibleverseIndexCreator extends AbstractIndexCreator {

	const BOOK_SUB_PATH = 'biblebooks';

	/** @var $entityManager EntityManager */
	protected $entityManager;

	/** @var  $bibelstellenService BibleVerseService */
	protected $bibelstellenService;


	/**
	 * IndexCreationService constructor.
	 *
	 * @param EntityManager     $entityManager
	 * @param BibleVerseService $bibleVerseService
	 */
	public function __construct(EntityManager $entityManager, BibleVerseService $bibleVerseService) {
		$this->entityManager       = $entityManager;
		$this->bibelstellenService = $bibleVerseService;
	}


	public function writeMainIndex(OutputInterface $output) {
		$this->writeBibelstellenIndex($this->getMainOutputFile());
	}

	protected function writeBibelstellenIndex($indexFilePath) {
		$repo      = $this->entityManager->getRepository('LinkBundle:Bibelstelle');
		$allBooks  = $repo->getExistingBooksByGrabberIds($this->getGrabberIdsToUse());
		$bookIndex = [];

		foreach ($allBooks as $bookId) {
			$bookNameShort = $this->bibelstellenService->getBookStringFromId($bookId, 'short');
			$bookLinkId    = $this->getHtmlIdFromBookId($bookId);
			$bookUrl       = $this->getBookFilePathFromBookId($bookId);
			$bookUrl       = ($indexFilePath == $bookUrl) ? '' : $this->getRelativePath($indexFilePath, $bookUrl);
			$bookIndex[]   = "<a href='{$bookUrl}#{$bookLinkId}'>{$bookNameShort}</a>\n";
		}

		$html = '<h1>Bibelstellen Index</h1></h1><div class="indexIntend">' . join($bookIndex, ' | ') . '</div>';

		$this->writeToFile($html, $indexFilePath);
	}

	protected function getHtmlIdFromBookId($bookId) {
		$bookName = $this->bibelstellenService->getBookStringFromId($bookId);

		return $this->getHtmlIdFromString($bookName);
	}

	public function getBookFilePathFromBookId($bookId) {
		if ($this->splitOutputToMultipleFiles === TRUE) {
			$dir = dirname($this->mainOutputFile) . DIRECTORY_SEPARATOR . self::BOOK_SUB_PATH . DIRECTORY_SEPARATOR . $this->getHtmlIdFromBookId($bookId) . '.html';
		} else {
			$dir = $this->mainOutputFile;
		}

		return $dir;
	}

	public function writeDetailIndex(OutputInterface $output) {
		$repo    = $this->entityManager->getRepository('LinkBundle:Bibelstelle');
		$bookIds = $repo->getExistingBooksByGrabberIds($this->grabberIdsToUse);

		$countMaterialien = $repo->countBibelStellenByGrabber($this->grabberIdsToUse);
		$progressBar      = new ProgressBar($output);
		$progressBar->setFormat("Create: %book%\n%current:7s%/%max:-7s% [%bar%] %percent:3s%% %memory:6s% %elapsed:6s%/%estimated:-6s%");
		$progressBar->setMessage('', 'book');
		$progressBar->start($countMaterialien);

		foreach ($bookIds as $bookId) {
			$bookFilePath = $this->getBookFilePathFromBookId($bookId);

			if ($this->splitOutputToMultipleFiles === TRUE) {
				$this->startHtml($bookFilePath);
				$this->writeHtmlHead($bookFilePath);
				$this->startBody($bookFilePath);
			}

			$progressBar->setMessage($this->bibelstellenService->getBookStringFromId($bookId), 'book');
			$this->writeBibleBookMaterial($bookId, $progressBar);

			if ($this->splitOutputToMultipleFiles === TRUE) {
				$this->endBody($bookFilePath);
				$this->endHtml($bookFilePath);
			}

			gc_collect_cycles();
		}
	}

	protected function writeBibleBookMaterial($bookId, ProgressBar $progressBar) {
		$repo         = $this->entityManager->getRepository('LinkBundle:Bibelstelle');
		$page         = 1;
		$bookNameLong = $this->bibelstellenService->getBookStringFromId($bookId);
		$bookLinkId   = $this->getHtmlIdFromBookId($bookId);
		$lastChapter  = 0;
		$bookFilePath = $this->getBookFilePathFromBookId($bookId);

		// Buch-Überschrift
		$this->writeToFile("<h2 id='{$bookLinkId}' class='bibelbuch'>{$bookNameLong}</h2>", $bookFilePath);
		$this->writeBookChapterIndex($bookId, $bookFilePath);

		// Einzelne Bibelstellen /  Material
		while ($bibelstellen = $repo->getBibelStellenByGrabber($bookId, $this->grabberIdsToUse, $page++)) {
			$html = '';

			foreach ($bibelstellen as $bibelStelle) {
				try {
					$bibText = htmlentities($this->bibelstellenService->bibleVerseToString($bibelStelle, 'short',
																						   'de'));

					if ($bibelStelle->getFromChapter() > $lastChapter) {
						$lastChapter = $bibelStelle->getFromChapter();
						$chapterId   = $this->getBookChapterId($bookId, $bibelStelle->getFromChapter());
						$html        .= "<span class='newChapter' id='{$chapterId}'></span>";
					}

					$html .= "<li class='bibelstelle'><span class='bible'></span>{$bibText}</li>";

				} catch (\Exception $e) {
					// var_dump($bibelStelle->getId());
					echo "Could not convert bibleverse to string ... STRANGE\n";
					continue;
				}

				$progressBar->advance();
				// var_dump($bibelStelle->__toString());

				$materialien = $bibelStelle->getMaterial();
				$html        .= '<ul  class="materialListe">';
				/** @var $materialien Material[] */
				foreach ($materialien as $mat) {
					$html .= $this->generateHTMLMaterialEintrag($mat, $bookFilePath, $this->onlineVersion);
				}
				$html .= '</ul>';
			}

			$this->writeToFile($html, $bookFilePath);
			$html = NULL;
			$this->entityManager->clear();

			gc_collect_cycles();
		}
	}

	public function writeBookChapterIndex($bookId, $outputFile) {
		$repo             = $this->entityManager->getRepository('LinkBundle:Bibelstelle');
		$allBookChapters  = $repo->getAllExistingChaptersOfABook($bookId);
		$bookChapterIndex = [];

		foreach ($allBookChapters as $chapterNo) {
			$bookChapterLinkId  = $this->getBookChapterId($bookId, $chapterNo);
			$bookChapterIndex[] = "<a class='chapterIndex' href='#{$bookChapterLinkId}'>{$chapterNo}</a>";
		}

		$html = '<div class="bookIndex">Kapitel: ' . join($bookChapterIndex, ' | ') . '</div>';

		$this->writeToFile($html, $outputFile);
	}

	protected function getBookChapterId($bookId, $chapter) {
		return $this->getHtmlIdFromBookId($bookId) . '-' . $chapter;
	}

	protected function generateHTMLMaterialEintrag(Material $material, $outputFilename, $onlineVersion) {

		if ($onlineVersion === TRUE) {
			$href = ($material->getLink()->getUrl());
		} else {
			$href = $this->getRelativePath($outputFilename, $material->getLink()->getFilePath());
		}

		$title          = htmlentities($material->getTitel());
		$pool           = htmlentities($material->getPool());
		$desc           = htmlentities($material->getBeschreibung());
		$tags           = $material->getStichwort()->toArray();
		$htmlStichworte = '';

		// @see: https://codepen.io/wbeeftink/pen/dIaDH
		/*
			<ul class="tags">
				<li><a href="#" class="tag">HTML</a></li>
				<li><a href="#" class="tag">CSS</a></li>
				<li><a href="#" class="tag">JavaScript</a></li>
			</ul>
		 */
		if ($tags) {
			$htmlStichworte .= "<ul class='tags'>";
			/** @var Stichwort $stichwort */
			foreach ($tags as $stichwort) {
				$htmlStichworte .= " <li><a href='#' class='tag'>" . $stichwort->getText() . "</a></li>";
			}
			$htmlStichworte .= "</ul>\n";
		}

		$html = '';
		$html .= "<li class='material closed'";
		$html .= ($desc) ? " onclick='toggleClass(this,\"closed\");'><span class='arrow'></span>" : ">";
		$html .= "{$title} ({$pool})<a class='download' href='{$href}'></a>";
		$html .= ($desc) ? "<div class='desc'>{$desc}</div>" : "";
		$html .= $htmlStichworte;
		$html .= '</li>';

		return $html;
	}

	public function cleanupBevoreAnythingElse(InputInterface $input, OutputInterface $output) {
		$repo    = $this->entityManager->getRepository('LinkBundle:Bibelstelle');
		$bookIds = $repo->getExistingBooksByGrabberIds($this->grabberIdsToUse);

		foreach ($bookIds as $bookId) {
			$bookFilePath = $this->getBookFilePathFromBookId($bookId);
			$this->touchFile($bookFilePath, $alsoDeleteFile = TRUE);
		}

		// Cleanup Main Index-File
		$this->touchFile($this->getMainOutputFile(), $alsoDeleteFile = TRUE);
	}
}