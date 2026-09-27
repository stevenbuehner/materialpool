<?php
/**
 * This file was created by  steven
 * Created: 30.08.16 14:58
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\TagExtraction\Properties;

use App\Models\Material;

class RatingProperty extends Property {

	static $icon = 'properties/author.svg';
	static $type = 'author';

	public function __construct($value, $relevance = 0) {
		parent::__construct($value, $relevance);

		// to use rules
		$this->setValue($value);
	}

	public function setValue($value) {

		$value = intval($value);
		$value = max(0, $value);
		$value = min($value, Material::MAX_RATING);

		parent::setValue($value);
	}

	/**
	 * The function has to insert it's own value into the item
	 *
	 * @param Material $material
	 */
	function insertYourselfToItem(Material $material) {
		$material->rating = $this->getValue();
	}
}