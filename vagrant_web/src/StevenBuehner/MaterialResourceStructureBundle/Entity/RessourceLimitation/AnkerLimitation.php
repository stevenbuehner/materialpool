<?php
namespace StevenBuehner\MaterialResourceStructureBundle\Entity\RessourceLimitation;

class AnkerLimitation implements ResourceLimitationInterface {

	/**
	 * @var string
	 */
	protected $anker;


	public function __construct() {
		$this->setAnker('');
	}

	/**
	 * @return string
	 */
	public function getAsTextLabel() {
		// TODO: Implement getAsTextLabel() method.
		return 'MISSING IMPLEMENTATION';
	}

	/**
	 * @return string
	 */
	public function getAsHtmlLabel() {
		// TODO: Implement getAsHtmlLabel() method.
		return 'MISSING IMPLEMENTATION';
	}

	/**
	 * @return string
	 */
	public function getAnker() {
		return $this->anker;
	}

	/**
	 * @param string $anker
	 */
	public function setAnker($anker) {
		$this->anker = $anker;
	}


}