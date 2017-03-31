<?php
/**
 * This file was created by  steven
 * Created: 30.08.16 14:58
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\TagExtraction\Properties;

use App\Models\Material;

class TitleProperty extends Property {

	static $icon = 'properties/title.svg';
	static $type = 'title';

	/**
	 * The function has to insert it's own value into the item
	 *
	 * @param Material $material
	 */
	function insertYourselfToItem(Material $material) {
		$material->title = $this->getValue();
	}
}