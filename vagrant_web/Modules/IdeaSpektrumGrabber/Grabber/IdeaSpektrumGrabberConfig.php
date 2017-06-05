<?php

namespace Modules\IdeaSpektrumBundle\Grabber;

use Modules\MaterialGrabber\Entities\GrabberConfig;
use Modules\MaterialGrabber\GrabberTemplates\DefaultUserPasswordGrabberConfig;
use Symfony\Component\Console\Question\Question;

class IdeaSpektrumGrabberConfig extends DefaultUserPasswordGrabberConfig {


	public function __construct(GrabberConfig $grabberConfig) {
		parent::__construct($grabberConfig);
		$this->defaultUsername = '';
	}

	/**
	 * Expects an associative array of Questions.
	 * The result of all Questions will be sent back to saveParameter($keyOfAssociativeArray, $userResultValue)
	 *
	 * @return Question[]
	 */
	function getConfigQuestions() {
		$questions = [];

		$oldVal                     = $this->getUsername();
		$questions [self::USERNAME] = new Question("Kundennummer ({$oldVal}): ", $oldVal);

		$oldVal                     = $this->getPasswort();
		$questions [self::PASSWORT] = new Question("Postleitzahl/Passwort ({$oldVal}): ", $oldVal);

		$oldVal = $this->getStoragePath();
		$q      = new Question("Unterverzeichnis zum Speichern ({$oldVal}): ", $oldVal);
		$q->setValidator(function ($answer) {
			if (empty($answer) || strlen($answer) < 3) {
				throw new \RuntimeException(
					'The Name of the subfolder needs to be at least 3 chars long.'
				);
			}

			return $answer;
		});
		$q->setMaxAttempts(3);
		$questions [self::STORAGE_PATH] = $q;

		return $questions;
	}


}