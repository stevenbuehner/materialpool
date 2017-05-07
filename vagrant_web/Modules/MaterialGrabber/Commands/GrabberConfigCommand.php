<?php

namespace Modules\MaterialGrabber\Commands;

use Doctrine\ORM\EntityManager;
use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Modules\MaterialGrabber\Services\GrabberService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class GrabberConfigCommand extends ContainerAwareCommand {

	const YES_VALUE = 'yes';
	const NO_VALUE  = 'no';

	protected function configure() {
		$this
			->setName('grabber:config')
			->setDescription('...')
			->addArgument('service', InputArgument::OPTIONAL, 'Service to configure', 'all');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		/** @var GrabberService $grabService */
		/** @var  EntityManager $entityManager */
		$grabService   = $this->getContainer()->get('link.grabbereservice');
		$entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

		$grabbers = $grabService->getAllGrabbers();
		$table    = $this->getGrabberTable($grabbers, $output);
		$table->render();

		/** @var SymfonyQuestionHelper $helper */
		$helper   = $this->getHelper('question');
		$question = $this->chooseGrabberConfigQuestion($grabbers);

		while (($resultGrabberName = $helper->ask($input, $output, $question))) {
			$resultGrabber   = $grabService->getGrabberByName($resultGrabberName);
			$resultGrabConf  = $resultGrabber->getGrabberConf();
			$resultGrabQuest = $resultGrabConf->getConfigQuestions();

			// Activate / Deactivate
			$activate = $helper->ask($input, $output, $this->chooseActivation($resultGrabber));
			$activate = $activate == self::YES_VALUE;
			$resultGrabber->setActive($activate);

			if ($resultGrabber->isActive()) {
				foreach ($resultGrabQuest as $key => $q) {
					$answ = $helper->ask($input, $output, $q);
					$resultGrabConf->saveParameter($key, $answ);
				}

				// The Update is only cascade persisted, when something changes in GrabberInfoItself
				// That's why we add every Config Value itself to the persist-chain
				foreach ($resultGrabConf->getGrabberInfo()->getConfigValues() as $d) {
					$entityManager->persist($d);
				}
			}

			$entityManager->persist($resultGrabConf->getGrabberInfo());
			$entityManager->flush();

			$output->writeln('The Configuration for ' . $resultGrabberName . ' was updated ...');

			$table = $this->getGrabberTable($grabbers, $output);
			$table->render();
		}

	}

	/**
	 * @param AbstractGrabber[] $grabbers
	 * @param OutputInterface   $output
	 */
	protected function getGrabberTable($grabbers, OutputInterface $output) {
		$table = new Table($output);
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
