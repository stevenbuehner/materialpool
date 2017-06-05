<?php

namespace Modules\MaterialGrabber\Commands;

use GuzzleHttp\Exception\ConnectException;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\Services\GrabManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GrabberRunCommand extends GrabberCommandTemplate {
	/** @var  GrabManager */
	protected $grabManager;

	protected function configure() {
		parent::configure();

		$this->setName('grabber:run');
	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		parent::initialize($input, $output);

		$this->grabManager = $this->getContainer()->get('link.grabmanager');
	}

	protected function runBeforeGrabberLopp(InputInterface $input, OutputInterface $output) {
		// Nothing to do
	}

	protected function runGrabberLoop(InputInterface $input, OutputInterface $output, AbstractGrabber $grabber) {
		// Check Config
		$output->write('Validating config for ' . $grabber->getName() . '..');
		if (!$grabber->isConfigValid()) {
			$output->writeln('.. failed! Due to invalid configuration.');

			return;
		} else {
			$output->writeln('. done.');
		}

		try {
			$output->writeln('Start Grabbing with: ' . $grabber->getName());
			$grabber->beforeGrabbing();
		} catch (ConnectException $e) {
			// i.e. connection refused (because site not available
			$output->writeln("<error>" . $e->getMessage() . "</error>\n");

			return;
		}

		try {
			$progressBar = new ProgressBar($output);
			$progressBar->setFormat('Prepare  %current:7s%/%max:-7s% [%bar%] %percent:3s%% %memory:6s%');
			$this->grabManager->runNeedsGrabbing($grabber, $progressBar, $this->forceGrabbing);
			$progressBar->finish();
			$output->writeln('');
		} catch (ConnectException $e) {
			// i.e. connection refused (because site not available
			$progressBar->finish();
			$output->writeln("<error>" . $e->getMessage() . "</error>\n");

			return;
		}

		$progressBar = new ProgressBar($output);
		$progressBar->setFormat('Download %current:7s%/%max:-7s% [%bar%] %percent:3s%% %memory:6s% %elapsed:6s%/%estimated:-6s%');
		$this->grabManager->runGrabLinks($grabber, $progressBar, $this->forceGrabbing);
		$output->writeln(''); // Clear space after ProgressBar

		$grabber->afterGrabbing();
		$grabber->getGrabberConf()->setLastRun(new \DateTime('now'));

		// Empty-Line after Grabbing
		$output->writeln('');

		// Write and clear GrabberConf
		$em = $this->getContainer()->get('doctrine.orm.entity_manager');
		$em->flush($grabber->getGrabberConf()->getGrabberConfig());
	}

	protected function runAfterGrabberLopp(InputInterface $input, OutputInterface $output) {
		$this->grabManager->displayFailedLinks($this->useGabbers, $output);

		$output->writeln('Done Grabbing');
	}
}
