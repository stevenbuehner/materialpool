<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ForeignResourceId
 *
 * @package App\Models
 * @property int $id
 * @property int $resource_id
 * @property int $foreign_id
 * @property          $created_at
 * @property          $updated_at
 * @property Resource $resource
 * @property User $user
 * @property Bundle $bundle
 * @property int $user_id
 */
class ForeignResourceId extends Model {

	protected $table = 'resource_foreign_ids';

	protected $fillable = [
		'resource_id', 'foreign_id', 'user_id', 'bundle_id'

	];

	public function getRouteKeyName() {
		return 'foreign_id';
	}

	/**
	 * Return the resource assigned
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function resource() {
		return $this->belongsTo(Resource::class);
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

	public function toArray() {
		$resource = $this->resource;

		return array_merge($resource->toArray(), ['id' => $this->foreign_id]);
	}

}
