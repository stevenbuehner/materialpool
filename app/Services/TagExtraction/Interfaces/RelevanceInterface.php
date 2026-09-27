<?php

namespace App\Services\TagExtraction\Interfaces;

interface RelevanceInterface {

	const RELEVANCE_EXIF_MIN = 40;
	const RELEVANCE_EXIF_MAX = 49;

	const RELEVANCE_USER_MIN = 100;
	const RELEVANCE_USER_AVG = 200;
	const RELEVANCE_USER_MAX = 300;

	/**
	 *
	 * @return int $priority
	 * Relevance regulations (the higher the relevance/priority, the more important is the peace of imformation
	 * 0 => Not important at all (default)
	 * 1-99    => Automatically recognized values
	 *   1-19    => guessed values based on filenames etc.
	 *   40-49   => guessed values based on exif-data etc.
	 *   80-99   => guessed based on other informations (checksums former files, etc.)
	 * 100-199 => User-Input value (more important)
	 * 300     => Explicitly set value by the user (most important)
	 *
	 */
	public function getRelevance();

	/**
	 * @param int $priority
	 */
	public function setRelevance($priority);

}