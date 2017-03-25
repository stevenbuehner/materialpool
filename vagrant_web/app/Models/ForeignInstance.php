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

	/**
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function materials() {
		$query = Material::query();
		$query->select('materials.*')->distinct()->from('foreign_resource_keys')
			  ->join('material_resource', 'material_resource.resource_id', '=', 'foreign_resource_keys.resource_id')
			  ->join('materials', 'material_resource.material_id', '=', 'materials.id')
			  ->where('foreign_resource_keys.foreign_instance_id', $this->getKey());

		return $query;
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function resources() {
		$query = Resource::query();
		$query->select('resources.*')->distinct()->from('foreign_resource_keys')
			  ->join('resources', 'resources.id', '=', 'foreign_resource_keys.resource_id')
			  ->where('foreign_resource_keys.foreign_instance_id', $this->getKey());

		return $query;
	}

	/**
	 * Get the user that owns the foreignInstance
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function user() {
		return $this->belongsTo(User::class);
	}
}
