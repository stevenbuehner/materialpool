<?php

namespace App\Services\TagExtraction\Properties;

use App\Models\Material;
use App\Services\TagExtraction\Interfaces\CompareablePropertyInterface;
use App\Services\TagExtraction\Interfaces\PropertyInterface;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;

abstract class Property implements CompareablePropertyInterface, RelevanceInterface, PropertyInterface {

	protected $value;
	protected $relevance;

	public function __construct($value, $relevance = 0) {
		$this->value     = $value;
		$this->relevance = $relevance;
	}

	/**
	 * Returns a string, that represents the value of the instance to compare it with other instances
	 *
	 * @return string
	 */
	public function getCompareString() {
		return 'type=' . self::class . ',value=' . $this->getValue();
	}

	/**
	 *
	 * @return mixed $value
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 *
	 * @param mixed $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}

	/**
	 * The function has to insert it's own value into the item
	 *
	 * @param Material $material
	 */
	abstract function insertYourselfToItem(Material $material);

	public function __toString() {
		return 'r=' . $this->getRelevance() . ',v=' . ((string)$this->getValue());
	}

	/**
	 *
	 * @return int $priority
	 * Priory regulations (the higher the priority, the more important valid is it
	 * 0 => Not important at all (default)
	 * 1-99    => Automatically recognized values
	 *   1-19    => guessed values based on filenames etc.
	 *   40-49   => guessed values based on exif-data etc.
	 *   80-99   => guessed based on other informations (checksums former files, etc.)
	 * 100-199 => User-Input value (more important, i.e. in Textfile the first line)
	 * 300     => Explicitly set value by the user (most important)
	 *
	 */
	public function getRelevance() {
		return $this->relevance;
	}

	/**
	 * 0 = default
	 * 50 = Explicitly set by User in Meta-Data
	 * 80 = Explicitly overriden by User in Browser
	 * 100 = Explicitly overriden by admin
	 *
	 * @param int $relevance
	 */
	public function setRelevance($relevance) {
		$this->relevance = ( int )$relevance;
	}
}

?>