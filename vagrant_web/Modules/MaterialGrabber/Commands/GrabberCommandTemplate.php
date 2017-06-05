<?php

namespace Modules\MaterialGrabber\Commands;

use Illuminate\Console\Command;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\Services\GrabberService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class GrabberCommandTemplate extends Command {


	/** @var  GrabberService */
	protected $grabberService;

	/** @var  AbstractGrabber[] */
	protected $useGabbers;

	/** @var  bool */
	protected $forceGrabbing;

	protected function configure() {

		date_default_timezone_set('Europe/Berlin');

		$this
			->setDescription('...')
			->addArgument('grabber', InputArgument::OPTIONAL, 'Select grabber (optional)')
			->addOption('force', 'f', InputOption::VALUE_NONE, 'Force reloadinging the index');

	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		parent::initialize($input, $output);

		$this->grabberService = $this->getContainer()->get('link.grabbereservice');
		$this->useGabbers     = [];
		$this->forceGrabbing  = FALSE;

		// Don't log SQL-Statements => Memory Leaks
		// @see: https://coderwall.com/p/awzjhw/avoiding-memory-leaks-in-symfony2-doctrine-entitymanager

		// Loggen nur im Debug-Modus mit Option -vvv
		if (!$output->isDebug()) {
			$this->getContainer()->get('doctrine.orm.entity_manager')->getConnection()->getConfiguration()
				 ->setSQLLogger(NULL);
		}

	}

	protected function interact(InputInterface $input, OutputInterface $output) {
		parent::interact($input, $output);

		$grabberName = $input->getArgument('grabber');
		$grabber     = $this->grabberService->getGrabberByName($grabberName);

		if ($grabber === FALSE) {
			$this->useGabbers = $this->grabberService->getAllActiveGrabbers();

			if (empty($grabberName)) {
			} else {
				$output->writeln('The specified Grabber does not exist. Using all grabbers instead.');
			}
		} else {
			$this->useGabbers[] = $grabber;
		}

		if ($input->hasOption('force')) {
			$this->forceGrabbing = $input->getOption('force');
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		gc_enable();

		$this->runBeforeGrabberLopp($input, $output);

		foreach ($this->useGabbers as $key => $grabber) {

			$this->runGrabberLoop($input, $output, $grabber);

			gc_collect_cycles();
		}

		$this->runAfterGrabberLopp($input, $output);
	}

	abstract protected function runBeforeGrabberLopp(InputInterface $input, OutputInterface $output);

	abstract protected function runGrabberLoop(InputInterface $input, OutputInterface $output, AbstractGrabber $grabber);

	abstract protected function runAfterGrabberLopp(InputInterface $input, OutputInterface $output);
}
