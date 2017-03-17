<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForeignInstance extends Model {

	public function foreignResourceKeys() {
		return $this->hasMany(ForeignResourceKey::class);
	}
}
