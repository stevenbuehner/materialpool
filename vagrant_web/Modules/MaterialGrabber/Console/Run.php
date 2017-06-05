<?php

namespace Modules\MaterialGrabber\Console;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Console\Command;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\Services\GrabberService;
use Modules\MaterialGrabber\Services\GrabManager;
use Symfony\Component\Console\Helper\ProgressBar;

class Run extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = 'grabber:run {grabber?} {--f|force}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Run waiting downloads of the grabber.';

	/**
	 * @var GrabberService
	 */
	protected $grabberService;

	/** @var GrabManager */
	protected $grabManager;


	/**
	 * Create a new command instance.
	 *
	 * @param $grabberService GrabberService
	 *
	 */
	public function __construct(GrabberService $grabberService, GrabManager $grabManager) {
		parent::__construct();

		$this->grabberService = $grabberService;
		$this->grabManager    = $grabManager;
	}

	/**
	 * Execute the console command.
	 *
	 */
	public function handle() {
		gc_enable();

		// Options and arguments
		$grabbers = $this->getGrabbersToRun();
		$force    = $this->option('force');


		/**
		 * @var string          $key
		 * @var AbstractGrabber $grabber
		 */
		foreach ($grabbers as $key => $grabber) {

			gc_collect_cycles();

			$this->info('Validating config for ' . $grabber->getName() . '...');
			if (!$grabber->isConfigValid()) {
				$this->warn('... failed! Due to invalid configuration.');

				continue;
			}


			try {
				$this->info('Start Grabbing with: ' . $grabber->getName());
				$grabber->beforeGrabbing();
			} catch (ConnectException $e) {
				// i.e. connection refused (because site not available
				$this->error($e->getMessage());

				continue;
			}


			try {
				$progressBar = new ProgressBar($this->output);
				$progressBar->setFormat('Prepare  %current:7s%/%max:-7s% [%bar%] %percent:3s%% %memory:6s%');
				$this->grabManager->runNeedsGrabbing($grabber, $progressBar, $force);
				$progressBar->finish();
				$this->output->writeln('');
			} catch (ConnectException $e) {
				// i.e. connection refused (because site not available
				$progressBar->finish();
				$this->error($e->getMessage());

				continue;
			}


			$progressBar = new ProgressBar($this->output);
			$progressBar->setFormat('Download %current:7s%/%max:-7s% [%bar%] %percent:3s%% %memory:6s% %elapsed:6s%/%estimated:-6s%');
			$this->grabManager->runGrabLinks($grabber, $progressBar, $force);
			$this->output->writeln(''); // Clear space after ProgressBar

			$grabber->afterGrabbing();
			$grabber->getGrabberConf()->setLastRun(new \DateTime('now'));

			// Empty-Line after Grabbing
			$this->output->writeln('');

			gc_collect_cycles();
		}

		$this->grabManager->displayFailedLinks($grabbers, $this->output);

		$this->info('Done Grabbing');
	}

	protected function getGrabbersToRun() {

		$useGrabbers = [];

		$grabberName = $this->argument('grabber');


		if ($grabberName) {
			$grabber = $this->grabberService->getGrabberByName($grabberName);

			if ($grabber === FALSE) {
				$this->error('The specified Grabber does not exist. Using all grabbers instead.');
			} else {
				$useGrabbers[] = $grabber;
			}
		} else {
			$useGrabbers = $this->grabberService->getAllActiveGrabbers();
		}

		return $useGrabbers;
	}

}
