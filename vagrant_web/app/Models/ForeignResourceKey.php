<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForeignResourceKey extends Model {

	protected $casts   = [
		'remote_id'           => 'int',
		'foreign_instance_id' => 'int',
		'resource_id'         => 'int'
	];
	protected $guarded = [];

	public function instance() {
		return $this->belongsTo(ForeignInstance::class);
	}

	public function resource() {
		return $this->belongsTo(Resource::class);
	}
}
