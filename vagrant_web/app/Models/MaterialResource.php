<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class MaterialResource
 *
 * @property object $limitation
 */
class MaterialResource extends Pivot {

	public function getLimitationAttribute($value) {
		return unserialize($value);
	}

}
