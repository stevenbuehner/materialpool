<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class MaterialResource
 *
 * @property object $limitation
 */
class MaterialResource extends Pivot {

	protected $casts = [
	];


	public function getLimitationAttribute() {
		return unserialize($this->attributes['limitation']);
	}

	public function setLimitationAttribute($limitation) {
		$this->attributes['limitation'] = serialize($limitation);

		return $this;
	}


}
