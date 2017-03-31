<?php
/**
 * This file was created by  steven
 * Created: 30.08.16 14:58
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\TagExtraction\Properties;

use App\Models\Material;
use App\Models\Person;

class AuthorProperty extends Property {

	static $icon = 'properties/author.svg';
	static $type = 'author';

	/**
	 * The function has to insert it's own value into the item
	 *
	 * @param Material $material
	 */
	function insertYourselfToItem(Material $material) {
		$author = Person::firstOrCreate($this->getValue());
		$material->author()->save($author);
	}
}