<?php

namespace Modules\MaterialGrabber\Commands;

use Doctrine\ORM\EntityManager;
use Modules\MaterialGrabber\IndexCreation\AbstractIndexCreator;
use Modules\MaterialGrabber\IndexCreation\MainIndexCreator;
use Modules\MaterialGrabber\Services\GrabberService;
use Modules\MaterialGrabber\Services\LinkManager;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GrabberCreateIndexCommand extends ContainerAwareCommand {
	/**
	 * @var $linkManager LinkManager
	 */
	protected $linkManager;

	/**
	 * @var $entityManager EntityManager
	 */
	protected $entityManager;

	/**
	 * @var $bibelstellenService BibleVerseService
	 */
	protected $bibelstellenService;

	/** @var  GrabberService */
	protected $grabberService;


	/** @var  boolean */
	protected $onlineVersion;
	/** @var  boolean */
	protected $splitOutputToMultipleFiles;
	/** @var  string */
	protected $mainOutputFile;
	/** @var  string[] */
	protected $grabberNamesToUse;
	/** @var  int[] */
	protected $grabberIdsToUse;
	/** @var  string[] */
	protected $indexTypes;

	protected function configure() {

		$this
			->setName('grabber:create:index')
			->setDescription('Generate an html-index file with links to the resources')
			->addArgument('output', InputArgument::OPTIONAL, 'Output-File to store the index.html file in')
			->addArgument('type', InputArgument::OPTIONAL,
						  'The type of index that will be created. Accepted values are "bible", "index", and "all"')
			->addArgument('grabbers', InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
						  'A list of grabbers to use for the output.')
			->addOption('online', 'on', InputOption::VALUE_NONE,
						'Flag indicates, that the links should be generated as ONLINE-Links')
			->addOption('split', 's', InputOption::VALUE_NONE, 'Add this flag to split the result into seperate files');
	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		parent::initialize($input, $output);
		gc_enable();

		$this->linkManager         = self::getContainer()->get('link.linkmanager');
		$this->grabberService      = self::getContainer()->get('link.grabbereservice');
		$this->entityManager       = self::getContainer()->get('doctrine.orm.entity_manager');
		$this->bibelstellenService = self::getContainer()->get('bible_verse.helper');

		// Parse or init Parameters
		// Init parameters
		$this->onlineVersion              = FALSE;
		$this->splitOutputToMultipleFiles = FALSE;
		$this->mainOutputFile             = $this->getDefaultOutputPath($this->splitOutputToMultipleFiles);
		$this->grabberNamesToUse          = [];
		$this->grabberIdsToUse            = [];
		$this->indexTypes                 = ['bible', 'index']; // 'bible', 'index'
		// Todo: create an event to register IndexTypes dynamically

		// Loggen nur im Debug-Modus mit Option -vvv
		if (!$output->isDebug()) {
			$this->getContainer()->get('doctrine.orm.entity_manager')->getConnection()->getConfiguration()
				 ->setSQLLogger(NULL);
		}
	}

	/** @return string */
	protected function getDefaultOutputPath($isSplitFile) {
		return $isSplitFile ? 'indexBundle/index.html' : 'index.html';
	}

	protected function interact(InputInterface $input, OutputInterface $output) {
		parent::interact($input, $output);

		// Parse Input Parameters and override defaults if parameter is given

		// Online Version?
		$onl = $input->getOption('online');
		if ($onl) {
			$this->onlineVersion = $onl ? TRUE : FALSE;
		}

		// Split index?
		$this->splitOutputToMultipleFiles = $input->getOption('split');

		// (main) output File
		$out = $input->getArgument('output');
		if ($out) {
			$this->mainOutputFile = $out;
		} else {
			$this->mainOutputFile = $this->getDefaultOutputPath($this->splitOutputToMultipleFiles);
		}
		// Create folder structure and make path absolute
		$this->mainOutputFile = $this->getOutputFilePath();


		// Which grabbers to use? Use all, if none is given
		$grabbers = $input->getArgument('grabbers');
		if (count($grabbers) > 0) {
			// Use the selected grabbers
			foreach ($grabbers as $grab) {
				$grabber = $this->grabberService->getGrabberByName($grab);

				if ($grabber === FALSE) {
					// Todo Interact with user to find out what grabber he ment
					$output->writeln("The Grabber '{$grab}' does not exist!");
				} else {
					$this->grabberNamesToUse[] = $grabber->getGrabberConf()->getName();
					$this->grabberIdsToUse[]   = $grabber->getGrabberConf()->getId();
				}
			}
		} else {
			// Use all grabbers
			$allGrabbers = $this->grabberService->getAllActiveGrabbers();
			foreach ($allGrabbers as $grabber) {
				$this->grabberNamesToUse[] = $grabber->getGrabberConf()->getName();
				$this->grabberIdsToUse[]   = $grabber->getGrabberConf()->getId();
			}
		}

		// Which index Type(s) to use
		$type = $input->getArgument('type');
		switch ($type) {
			case 'bibel':
			case 'bible':
				$this->indexTypes = ['bible'];
				break;
			case 'index':
				$this->indexTypes = ['index'];
				break;
			case'all':
			case NULL:
				$this->indexTypes = ['index', 'bible'];
				break;
			default:
				$output->writeln("The IndexType '{$type}' does not exist!");
		}

	}

	/**
	 * Returns an absolute File path, where to store the output-index
	 *
	 * @return string
	 */
	protected function getOutputFilePath() {
		$filePath = $this->mainOutputFile;

		// Path is relative
		if (substr($filePath, 0, 1) != '/') {
			$filePath = $this->linkManager->getDownloadFilePath() . DIRECTORY_SEPARATOR . $filePath;
		}

		if (!file_exists(dirname($filePath))) {
			mkdir(dirname($filePath), 0777, TRUE);
		}

		// Path is relative
		return $filePath;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$indexPath = $this->getOutputFilePath();
		$this->setupMainIndexFile($indexPath);

		$this->generateIndex($indexPath, $input, $output);

		$output->writeln('All Done...');
	}

	protected function setupMainIndexFile($indexPath) {
		if (file_exists($indexPath)) {
			unlink($indexPath);
		}

		touch($indexPath);
	}

	public function generateIndex($mainIndexFilePath, InputInterface $input, OutputInterface $output) {

		// Setup main index-file (startpoint for searching)
		$mainIndexCreator = new MainIndexCreator();
		$mainIndexCreator->startHtml($mainIndexFilePath);
		$mainIndexCreator->writeHtmlHead($mainIndexFilePath);
		$mainIndexCreator->startBody($mainIndexFilePath);

		foreach ($this->indexTypes as $indexType) {
			switch ($indexType) {
				case 'bible':
					$creator = $this->getContainer()->get('link.indexcreator.bible');
					$this->configureIndexCreator($creator);
					$creator->cleanupBevoreAnythingElse($input, $output);
					break;
				case 'index':
					break;
			}
		}

		foreach ($this->indexTypes as $indexType) {
			switch ($indexType) {
				case 'bible':
					$creator = $this->getContainer()->get('link.indexcreator.bible');
					$this->configureIndexCreator($creator);
					$creator->writeMainIndex($output);
					break;
				case 'index':
					break;
			}
		}

		foreach ($this->indexTypes as $indexType) {
			switch ($indexType) {
				case 'bible':
					$creator = $this->getContainer()->get('link.indexcreator.bible');
					$this->configureIndexCreator($creator);
					$creator->writeDetailIndex($output);
					break;
				case 'index':
					break;
			}
		}

		// Close main índex-file (startpoint for searching)
		$mainIndexCreator->endBody($mainIndexFilePath);
		$mainIndexCreator->endHtml($mainIndexFilePath);
	}

	/**
	 * @param AbstractIndexCreator $creator
	 * @return AbstractIndexCreator
	 */
	protected function configureIndexCreator(AbstractIndexCreator $creator) {
		$creator->setGrabberIdsToUse($this->grabberIdsToUse);
		$creator->setGrabberNamesToUse($this->grabberNamesToUse);
		$creator->setIndexTypes($this->indexTypes);
		$creator->setOnlineVersion($this->onlineVersion);
		$creator->setSplitOutputToMultipleFiles($this->splitOutputToMultipleFiles);
		$creator->setMainOutputFile($this->mainOutputFile);

		return $creator;
	}

}
