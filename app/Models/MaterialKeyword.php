<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class MaterialResource
 *
 * @property int $relevance
 */
class MaterialKeyword extends Pivot {


	public function setRelevanceAttribute($relevance) {
		$this->attributes['relevance'] = (int)($relevance);
	}

	public function toArray() {
		return ['relevance' => $this->getRelevanceAttribute($this->attributes['relevance'])];
	}

	/**
	 * This is needed to cast NULL into 0
	 *
	 * @return int
	 */
	public function getRelevanceAttribute($relevance) {
		return (int)$relevance;
	}


}
