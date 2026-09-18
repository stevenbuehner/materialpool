<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ForeignMaterialId
 *
 * @package App\Models
 * @property int $id
 * @property int $material_id
 * @property int $foreign_id
 * @property int $user_id
 * @property string $scope_key
 * @property          $created_at
 * @property          $updated_at
 * @property Material $material
 * @property User $user
 * @property Bundle $bundle
 */
class ForeignMaterialId extends Model {
	use HasFactory;

	protected $table = 'material_foreign_ids';

	protected $fillable = [
		'material_id', 'foreign_id', 'user_id', 'bundle_id', 'scope_key'
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
