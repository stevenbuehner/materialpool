<?php

namespace Modules\MaterialGrabber\GrabberTemplates;

use Modules\MaterialGrabber\Entities\GrabberConf;
use Modules\MaterialGrabber\Entities\GrabberConfig;
use Symfony\Component\Console\Question\Question;

class DefaultUserPasswordGrabberConfig extends DefaultNoUserGrabberConfig {

	const USERNAME = "username";
	const PASSWORT = "passwort";

	protected $defaultUsername;
	protected $defaultPasswort;

	public function __construct(GrabberConfig $grabberConfig) {
		parent::__construct($grabberConfig);

		$this->defaultUsername = $this->getName();
		$this->defaultPasswort = '';
	}

	/**
	 * Expects an associative array of Questions.
	 * The result of all Questions will be sent back to saveParameter($keyOfAssociativeArray, $userResultValue)
	 *
	 * @return Question[]
	 */
	public function getConfigQuestions() {
		$questions = [];

		$oldVal                     = $this->getPasswort();
		$questions [self::PASSWORT] = new Question("Passwort ({$oldVal}): ", $oldVal);

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
	public function getPasswort() {
		return $this->getParameter(self::PASSWORT, $this->defaultPasswort);
	}

	/**
	 * @return string
	 */
	public function getUsername() {
		return $this->getParameter(self::USERNAME, $this->defaultUsername);
	}

	/**
	 * @param string $username
	 */
	public function setUsername($username) {
		$this->setParameter(self::USERNAME, $username);
	}

	/**
	 * @param string $passwort
	 */
	public function setPasswort($passwort) {
		$this->$this->setParameter(self::PASSWORT, $passwort);
	}
}