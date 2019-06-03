<?php

namespace App\ResourceLimitations;


use App\Models\DocumentFile;
use App\Models\PdfFile;
use App\Models\Resource;

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
	 * @return PageLimitation
	 */
	public function setPages(array $pages) {
		$this->pages = [];
		foreach ($pages as $page) {
			$this->pages[] = (int) $page;
		}

		$this->sortArrayAlphabetically();

		return $this;
	}

	/**
	 * @param int $pageNo
	 * @return PageLimitation
	 */
	public function addPage(int $pageNo) {
		if (!in_array($pageNo, $this->pages)) {
			$this->pages[] = $pageNo;
		}

		$this->sortArrayAlphabetically();

		return $this;
	}

	/**
	 *  $pages alphabetisch sortiert
	 */
	protected function sortArrayAlphabetically() {
		sort($this->pages);
	}

	/**
	 * @param int $pageNo
	 * @return PageLimitation
	 */
	public function removePage(int $pageNo) {
		if (($key = array_search($pageNo, $this->pages)) !== false) {
			unset($this->pages[$key]);
		}

		return $this;
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
	 * @throws InvalidLimitationRequestException
	 */
	public function insertFromWebValue(string $value) {
		$pages = preg_split('~\s*,\s*~', $value);

		foreach ($pages as $page) {
			if (!is_numeric($page)) {
				throw new InvalidLimitationRequestException();
			}
		}

		$this->setPages($pages);

		return $this;
	}

	/**
	 * Formats the limitation-data back to an string-value, which may be used in the webinterface
	 *
	 * @return string
	 */
	public function toWebValue() {
		return join(',', $this->getPages());
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

	/**
	 * Returns true if the given Resource is able to use this $limitation
	 *
	 * @param Resource $resource
	 * @return bool
	 */
	public function isResourceApplicable(Resource $resource) {
		return ($resource instanceof DocumentFile || $resource instanceof PdfFile);
	}
}