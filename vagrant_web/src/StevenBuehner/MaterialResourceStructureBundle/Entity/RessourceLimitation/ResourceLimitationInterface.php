<?php

namespace StevenBuehner\MaterialResourceStructureBundle\Entity\RessourceLimitation;

interface ResourceLimitationInterface {
	/**
	 * @return string
	 */
	public function getAsTextLabel();

	/**
	 * @return string
	 */
	public function getAsHtmlLabel();
}