<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ForeignInstance
 *
 * @property int    $id
 * @property string $name
 * @property string $info
 * @property string $api_key
 * @property        $created_at
 * @property        $updated_at
 */
class ForeignInstance extends Model {

	public function foreignResourceKeys() {
		return $this->hasMany(ForeignResourceKey::class);
	}
}
