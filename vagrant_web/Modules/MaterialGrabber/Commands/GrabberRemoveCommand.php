<?php

namespace Modules\MaterialGrabber\Commands;

use Modules\MaterialGrabber\GrabberTemplates\AbstractGrabber;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class GrabberRemoveCommand extends ContainerAwareCommand {
	protected function configure() {
		$this
			->setName('grabber:remove')
			->setDescription('Remove all Links from the database of a certain grabber');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		gc_enable();

		$grabberService  = $this->getContainer()->get('link.grabbereservice');
		$allGrabbers     = $grabberService->getAllGrabbers();
		$grabberQuestion = $this->chooseGrabberConfigQuestion($allGrabbers);

		/** @var SymfonyQuestionHelper $helper */
		$helper            = $this->getHelper('question');
		$resultGrabberName = $helper->ask($input, $output, $grabberQuestion);
		$chosenGrabber     = $grabberService->getGrabberByName($resultGrabberName);
		$chosenGrabberConf = $chosenGrabber->getGrabberConf();

		$em         = $this->getContainer()->get('doctrine.orm.entity_manager');
		$linkRepo   = $em->getRepository('LinkBundle:Link');
		$matRepo    = $em->getRepository('LinkBundle:Material');
		$countLinks = $linkRepo->countByGrabberIDs($chosenGrabberConf->getId());

		$progressBar = new ProgressBar($output);
		$progressBar->start($countLinks);

		// Lösche mit Grabber assoziierte Links und zugehöriges Material
		while ($foundLinks = $linkRepo->findBy(['grabber' => $chosenGrabberConf->getId()], [], 250)) {

			foreach ($foundLinks as $link) {
				$matRepo->removeMaterialAndAssociations($link);
				$em->remove($link);
				$em->flush($link);
				$progressBar->advance();
			}

			$em->clear();
			gc_collect_cycles();
		}

		$progressBar->finish();
		$output->writeln('');

		$output->writeln("All {$countLinks} links deleted, together with their associated material and data.");
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

}
