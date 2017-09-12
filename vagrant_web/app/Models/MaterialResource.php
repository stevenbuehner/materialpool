<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Class Material
 *
 * @package App\Models
 * @property object  $limitation
 * @property string  $title
 * @property string  $description
 * @property int     $rating (0-20)
 * @property boolean $from_bot
 * @property int     $created_by
 * @property int     $modified_by
 */
class MaterialResource extends Pivot {

	public function getLimitationAttribute($value) {
		return unserialize($value);
	}

}
