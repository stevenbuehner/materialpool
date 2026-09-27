<?php

namespace App\Services\TagExtraction\ResourceHandles;

interface TextContentInterface {

	/**
	 * @return string
	 */
	public function getContent();

	/**
	 * @param string $content
	 */
	public function setContent($content);

	/**
	 * Stores the first line, if it is needed in the future
	 *
	 * @param string $firstLine
	 * @return
	 */
	public function setFirstLine($firstLine);

	/**
	 * Returns the previously stored firstLine or returns FALSE if none has been stored yet
	 *
	 * @return string|FALSE
	 */
	public function getFirstLine();
}