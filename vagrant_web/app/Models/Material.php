<?php

namespace App\Models;

use App\ResourceLimitations\ResourceLimitationInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Material
 *
 * @package App\Models
 * @property object  $limitation
 * @property string  $title
 * @property string  $description
 * @property int     $rating
 * @property boolean $from_bot
 */
class Material extends Model {
	protected $casts = [
		'from_bot'    => 'boolean',
		'description' => 'string',
		'limitation'  => 'object'
	];

	protected $attributes = [
		'rating'      => NULL,
		'description' => ''
	];

	public function resources() {
		return $this->belongsToMany(Resource::class, 'material_resource', 'material_id', 'resource_id');
	}

	public function keywords() {
		return $this->belongsToMany(Keyword::class, 'keyword_material', 'material_id', 'keyword_id');
	}

	public function setLimitationAttribute(ResourceLimitationInterface $limitation) {
		$this->attributes['limitation'] = serialize($limitation);
	}

	public function getLimitationAttribute($value) {
		return unserialize($value);
	}


}
