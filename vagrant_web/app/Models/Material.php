<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model {
	protected $casts = [
		'boundary'    => 'object',
		'from_bot'    => 'boolean',
		'description' => 'string'
	];

	protected $attributes = [
		'rating'      => NULL,
		'description' => ''
	];

	public function resources() {

		return $this->belongsToMany(Resource::class, 'material_resource', 'material_id', 'resource_id');
	}


}
