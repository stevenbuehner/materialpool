<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ForeignMaterialId
 *
 * @package App\Models
 * @property $id
 * @property $material_id
 * @property $foreign_id
 * @property $created_at
 * @property $updated_at
 */
class ForeignMaterialId extends Model {

	protected $table = 'material_foreign_ids';

	protected $fillable = [
		'material_id', 'foreign_id', 'user_id'
	];

	public function getRouteKeyName() {
		return 'foreign_id';
	}

	/**
	 * Return the material assigned
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function material() {
		return $this->belongsTo(Material::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user() {
		return $this->belongsTo(User::class);
	}

}
