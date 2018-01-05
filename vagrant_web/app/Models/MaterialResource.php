<?php

namespace App\Models;

use App\ResourceLimitations\ResourceLimitationInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class MaterialResource
 *
 * @property object $limitation
 */
class MaterialResource extends Pivot {

	protected $casts = [
	];


	/**
	 * @return ResourceLimitationInterface|NULL
	 */
	public function getLimitationAttribute() {
		if ($this->attributes['limitation'] !== NULL) {
			return unserialize($this->attributes['limitation']);
		}

		return NULL;
	}

	/**
	 * @param ResourceLimitationInterface|NULL $limitation
	 * @return $this
	 */
	public function setLimitationAttribute(ResourceLimitationInterface $limitation = NULL) {
		$this->attributes['limitation'] = serialize($limitation);

		return $this;
	}


}
