<?php

namespace Modules\MaterialGrabber\Console;

use Illuminate\Console\Command;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\Services\GrabberService;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ChoiceQuestion;

class Config extends Command {

	const YES_VALUE = 'yes';
	const NO_VALUE  = 'no';


	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $signature = "grabber:config {service=all : The service to configure}";

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Configure Grabber Configs";


	protected $grabberService;

	/**
	 * Create a new command instance.
	 *
	 * @param $grabberService GrabberService
	 *
	 */
	public function __construct(GrabberService $grabberService) {
		parent::__construct();

		$this->grabberService = $grabberService;
	}


	public function handle() {

		$allGrabbers = $this->grabberService->getAllGrabbers();
		$table       = $this->getGrabberTable($allGrabbers);
		$table->render();

		/** @var SymfonyQuestionHelper $helper */
		$helper   = $this->getHelper('question');
		$question = $this->chooseGrabberConfigQuestion($allGrabbers);


		while (($resultGrabberName = $helper->ask($this->input, $this->output, $question))) {
			$resultGrabber   = $this->grabberService->getGrabberByName($resultGrabberName);
			$resultGrabConf  = $resultGrabber->getGrabberConf();
			$resultGrabQuest = $resultGrabConf->getConfigQuestions();

			// Activate / Deactivate
			$activate = $helper->ask($this->input, $this->output, $this->chooseActivation($resultGrabber));
			$activate = $activate == self::YES_VALUE;
			$resultGrabber->setActive($activate);
			$resultGrabConf->save();

			if ($resultGrabber->isActive()) {
				foreach ($resultGrabQuest as $key => $q) {
					$answ = $helper->ask($this->input, $this->output, $q);
					$resultGrabConf->setParameter($key, $answ);
				}
			}

			$this->info('The Configuration for ' . $resultGrabberName . ' was updated ...');


			$table = $this->getGrabberTable($allGrabbers);
			$table->render();
		}

	}

	/**
	 * @param AbstractGrabber[] $grabbers
	 * @return Table
	 */
	protected function getGrabberTable($grabbers) {
		$output = $this->getOutput();
		$table  = new Table($output);
		$table->setHeaders(['Grabber Name', 'Is active', 'Config valid', 'Last run', 'Author', 'Description']);

		$output->write('Validating config for: ');
		$firstRun = TRUE;

		foreach ($grabbers as $grabber) {
			$row   = [];
			$row[] = $grabber->getName();
			$row[] = $grabber->isActive() ? self::YES_VALUE : self::NO_VALUE;

			if ($grabber->isActive()) {
				$output->write($firstRun === FALSE ? ', ' : '');
				$output->write($grabber->getName());
				$firstRun = FALSE;

				$row[] = $grabber->isConfigValid() ? self::YES_VALUE : self::NO_VALUE;
			} else {
				$row[] = '?';
			}

			$row[] = $this->getElapsedTimeSinceLastRunString($grabber->getGrabberConf()->getLastRun());

			$row[] = $grabber->getAuthor();
			$row[] = $grabber->getDescription();

			$table->addRow($row);
		}
		$output->write('... done.', TRUE);

		return $table;
	}

	protected function getElapsedTimeSinceLastRunString($lastRun) {
		$result = '';

		if ($lastRun instanceof \DateTime) {
			$diff = $lastRun->diff(new \DateTime('now'));

			if ($diff->y >= 1) {
				$result = $diff->y . 'years ' . $diff->m . 'months';
			} else if ($diff->m >= 1) {
				$result = $diff->m . 'months ' . $diff->d . 'days';
			} else if ($diff->d > 1) {
				$result = $diff->d . 'days ' . $diff->h . 'h';
			} else if ($diff->h > 1) {
				$result = $diff->h . 'h ' . $diff->i . 'min';
			} else {
				$result = $diff->i . 'min ' . $diff->s . 'sec';
			}
		}

		return $result;

	}

	/**
	 * @param AbstractGrabber[] $grabbers
	 * @return ChoiceQuestion
	 */
	protected function chooseGrabberConfigQuestion($grabbers) {
		$choiches = [NULL];
		foreach ($grabbers as $grabber) {
			$choiches[] = $grabber->getName();
		}

		$question = new ChoiceQuestion(
			'Please select the Grabber you want to edit (ENTER for no change)',
			$choiches,
			NULL
		);

		$question->setAutocompleterValues($choiches);
		$question->setErrorMessage('The entered Grabbername "%s" is invalid.');
		$question->setMaxAttempts(3);


		return $question;
	}

	protected function chooseActivation(AbstractGrabber $grabber) {
		$currentStatus = $grabber->isActive() ? 'activated' : 'deactivated';
		$question      = new ChoiceQuestion("Do you want to activate grabber '{$grabber->getName()}'? (Currently: {$currentStatus}): ",
											[TRUE => self::YES_VALUE, FALSE => self::NO_VALUE],
											$grabber->isActive());

		return $question;
	}

}
