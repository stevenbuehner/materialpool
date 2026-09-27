<?php

namespace App\Services\TagExtraction\Interfaces;

use App\Models\Material;

interface PropertyInterface {

	/**
	 * @return mixed $value
	 */
	public function getValue();

	/**
	 * @param mixed $value
	 */
	public function setValue($value);

	/**
	 * The function has to insert it's own value into the item
	 *
	 * @param Material $material
	 */
	public function insertYourselfToItem(Material $material);

}