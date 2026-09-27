<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property string $scope_key
 */
class ForeignResourceId extends Model {
	use HasFactory;

	protected $table = 'resource_foreign_ids';

	protected $fillable = [
		'resource_id', 'foreign_id', 'user_id', 'bundle_id', 'scope_key'

	];

	protected static function booted(): void {
		static::creating(function (self $foreignId): void {
			$foreignId->scope_key ??= self::scopeKey($foreignId->bundle_id, $foreignId->user_id);
		});
	}

	public static function scopeKey(?int $bundleId, ?int $userId): string {
		return $bundleId !== null ? "bundle:{$bundleId}" : "user:{$userId}";
	}

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
