<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ForeignMaterialId
 *
 * @package App\Models
 * @property int $id
 * @property int $material_id
 * @property int $foreign_id
 * @property int $user_id
 * @property          $created_at
 * @property          $updated_at
 * @property Material $material
 * @property User $user
 * @property Bundle $bundle
 */
class ForeignMaterialId extends Model {

	protected $table = 'material_foreign_ids';

	protected $fillable = [
		'material_id', 'foreign_id', 'user_id', 'bundle_id'
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

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function bundle() {
		return $this->belongsTo(Bundle::class);
	}

}
