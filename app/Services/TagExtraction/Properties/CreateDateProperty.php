<?php
/**
 * This file was created by  steven
 * Created: 30.08.16 14:58
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\TagExtraction\Properties;

use App\Models\Material;
use DateTime;

/**
 * Class CreateDateProperty
 *
 * @package App\Services\TagExtraction\Properties
 * @method DateTime getValue
 */
class CreateDateProperty extends Property {

	/**
	 * The function has to insert it's own value into the item
	 *
	 * @param Material $material
	 */
	function insertYourselfToItem(Material $material) {
		$material->created_at = $this->getValue();
	}

	function getCompareString() {
		$d = $this->getValue();
		$d = ($d instanceof DateTime) ? $d->format('Y-m-d') : '';

		return 'type=' . self::class . ',value=' . $d;
	}

	public function __toString() {
		$date = ($this->getValue() !== NULL) ? $this->getValue()->getTimestamp() : '';

		return 'r=' . $this->getRelevance() . ',v=' . $date;
	}
}