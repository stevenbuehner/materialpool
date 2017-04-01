<?php

namespace App\Services\TagExtraction\ResourceHandles;

interface TextContentInterface {

	/**
	 * @return string
	 */
	public function getContent();
}