<?php

namespace App\Services\TagExtraction\Interfaces;

interface TagRecognitionInterface {

	/**
	 * If any special Tags are found, than they are returned
	 * They inherit from OCA\KnowledgeBase\Model\Tags\Tag
	 *
	 * @param String $stringValue
	 * @return array <Tag>
	 */
	public function extractSpecializedTag($stringValue);

	/**
	 * This function can be called to find out if other recognition classes should be run, although this one has found
	 * already some Tags
	 *
	 * @return boolean
	 */
	public function allowOtherRecognitionsOnSuccess();

	/**
	 * Integer Value between 0 and 100
	 * The highest values are always rendered first
	 *
	 * @return int
	 */
	public function getPriority();

}