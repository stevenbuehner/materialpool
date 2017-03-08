<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForeignResourceKey extends Model {

	public function instance() {
		return $this->belongsTo(ForeignInstance::class);
	}

	public function resource() {
		return $this->belongsTo(Resource::class);
	}
}
