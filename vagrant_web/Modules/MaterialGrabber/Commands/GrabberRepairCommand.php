<?php

namespace Modules\MaterialGrabber\Commands;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GrabberRepairCommand extends ContainerAwareCommand {

	/** @var  EntityManager */
	protected $em;

	protected function configure() {
		$this
			->setName('grabber:repair')
			->setDescription('Looking for Errors in the db and repairing them if possible.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		$grabberService = $this->getContainer()->get('link.grabbereservice');
		$this->em       = $this->getContainer()->get('doctrine.orm.entity_manager');
		$didRepairing   = FALSE;

		$count = $this->repairLinks();

		if ($count > 0) {
			$output->writeln("$count Links where repaired ...");
			$didRepairing = TRUE;
		}

		if (!$didRepairing) {
			$output->writeln('Apparently Nothing needed to be repaired');
		}

	}

	public function repairLinks() {
		$materialRepo = $this->em->getRepository('LinkBundle:Material');
		$countRepairs = 0;

		// Getting all material that have a link attached (but not vise versa) => bidirectional error
		$sql = "SELECT m.id FROM link l INNER JOIN material m ON (m.link_id = l.id) WHERE l.material IS NULL;";

		$stmt = $this->em->getConnection()->prepare($sql);
		$stmt->execute();
		$materialData = $stmt->fetchAll();

		foreach ($materialData as $row) {
			$id  = (int) $row['id'];
			$mat = $materialRepo->find($id);

			if (NULL !== $mat) {
				$countRepairs++;
				$link = $mat->getLink();
				$link->setMaterial($mat);
				$this->em->flush($link);
			}
		}

		return $countRepairs;
	}

}
