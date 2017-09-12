<?php

namespace App\ResourceLimitations;


class PageLimitation implements ResourceLimitationInterface {

	/** @var int[] $pages */
	protected $pages = [];

	/**
	 * TimeLimitation constructor.
	 */
	public function __construct() {
	}

	function jsonSerialize() {
		return ['pages' => $this->getPages()];
	}

	/**
	 * @return int[]
	 */
	public function getPages(): array {
		return $this->pages;
	}

	/**
	 * @param int[] $pages
	 */
	public function setPages(array $pages) {
		$this->pages = [];
		foreach ($pages as $page) {
			$this->pages[] = (int) $page;
		}

		// Alphabetisch sortiert
		sort($this->pages);
	}

	public function getLimitationView() {
		// TODO: Implement getLimitationView() method.
	}

	/** @return array */
	public function toArray() {
		return ['pages' => $this->getPages()];
	}

	/**
	 * Takes the string, used in the webinterface and extracts all the neccessary limitation data from it
	 *
	 * @return ResourceLimitationInterface
	 */
	public function insertFromWebValue(string $value) {
		$pages = preg_split('~\s*,\s*~', $value);
		$this->setPages($pages);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLimitationText() {

		$ranges    = [];
		$pageCount = count($this->pages);
		for ($i = 0; $i < $pageCount; $i++) {
			$rstart = $this->pages[$i];
			$rend   = $rstart;
			while ($i + 1 < $pageCount && $this->pages[$i + 1] - $this->pages[$i] == 1) {
				$rend = $this->pages[$i + 1]; // increment the index if the numbers sequential
				$i++;
			}
			$ranges[] = $rstart == $rend ? $rstart : $rstart . '-' . $rend;
		}

		$result = ($pageCount > 1) ? "Seiten " : "Seite ";
		$result .= join(', ', $ranges);

		return $result;
	}
}