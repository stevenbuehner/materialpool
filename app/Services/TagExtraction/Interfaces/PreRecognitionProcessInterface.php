<?php

namespace App\Services\TagExtraction\Interfaces;

interface PreRecognitionProcessInterface {

	/**
	 * preProcessTheInput change it if needed  ...
	 *
	 * @param string $value
	 * @param array $context
	 * @return array [$resultInputString, Collection]
	 */
	public function preProcessInput($inputValue, $context);


	/**
	 * Returns false if no recogntiion should take place on this string and true if to continue normally
	 *
	 * @return bool
	 */
	public function allowTagRecognitionAfterThis();

	/**
	 * Integer Value between 0 and 100
	 * The highest values are always rendered first
	 *
	 * @return int
	 */
	public function getPriority();


}