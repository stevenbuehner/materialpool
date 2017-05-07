<?php

namespace Modules\MaterialGrabber\GrabberTemplates;

use Modules\MaterialGrabber\Entities\GrabberConf;
use Symfony\Component\Console\Question\Question;

class DefaultNoUserGrabberConfig extends AbstractGrabberConfig {

	const STORAGE_PATH = "storage_path";

	protected $defaultStoragePath;

	public function __construct(GrabberConf $grabberInfo) {
		parent::__construct($grabberInfo);
		$this->defaultStoragePath = $grabberInfo->getName();
	}

	/**
	 * Expects an associative array of Questions.
	 * The result of all Questions will be sent back to saveParameter($keyOfAssociativeArray, $userResultValue)
	 *
	 * @return Question[]
	 */
	public function getConfigQuestions() {
		$questions = [];

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


	/**
	 * @return string
	 */
	public function getStoragePath() {
		return $this->getParameterValueOrDefault(self::STORAGE_PATH, $this->defaultStoragePath);
	}

	/**
	 * @param string $storagePath
	 */
	public function setStoragePath($storagePath) {
		$this->saveParameter(self::STORAGE_PATH, $storagePath);
	}
}