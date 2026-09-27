<?php
/**
 * This file was created by  steven
 * Created: 06.03.17 23:26
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\ResourceLimitations;

use App\Models\AudioFile;
use App\Models\Resource;
use App\Models\VideoFile;

class TimeLimitation implements ResourceLimitationInterface {

	/** @var float $start */
	protected $start = 0;

	/** @var float $end */
	protected $end = 0;

	/**
	 * TimeLimitation constructor.
	 *
	 * @param float $start
	 * @param float $end
	 */
	public function __construct() {
	}


	public function getLimitationView() {
		// TODO: Implement getLimitationView() method.
	}

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	function jsonSerialize() {
		return ['start' => $this->getStart(), 'end' => $this->getEnd()];
	}

	/**
	 * @return float
	 */
	public function getStart(): float {
		return $this->start;
	}

	/**
	 * @param float $start
	 */
	public function setStart(float $start) {
		$this->start = $start;
	}

	/**
	 * @return float
	 */
	public function getEnd(): float {
		return $this->end;
	}

	/**
	 * @param float $end
	 */
	public function setEnd(float $end) {
		$this->end = $end;
	}

	/** @return array */
	public function toArray() {
		return ['start' => $this->getStart(), 'end' => $this->getEnd()];
	}

	/**
	 * Takes the string, used in the webinterface and extracts all the neccessary limitation data from it
	 *
	 * @return ResourceLimitationInterface
	 */
	public function insertFromWebValue(string $value) {

		// float $start = 0.0, float $end = 999999.0
		// TODO: Implement insertFromWebValue() method.

		return $this;
	}

	/**
	 * Formats the limitation-data back to an string-value, which may be used in the webinterface
	 *
	 * @return string
	 */
	public function toWebValue() {
		// TODO: Implement toWebValue() method.

		return '';
	}

	/**
	 * @return string
	 */
	public function getLimitationText() {
		return 'von ' . $this->getStart() . '-' . $this->getEnd();
	}

	/**
	 * Returns true if the given Resource is able to use this $limitation
	 *
	 * @param Resource $resource
	 * @return bool
	 */
	public function isResourceApplicable(Resource $resource) {
		return ($resource instanceof VideoFile || $resource instanceof AudioFile);
	}
}