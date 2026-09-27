<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $place
 * @property string $reason
 * @property int $used_by_id
 * @property DateTime $datetime
 * @property DateTime $created_at
 * @property DateTime $updated_at
 * @property int $material_id
 * @property int $created_by
 * @property int $updated_by
 * @property User $user
 * @property User $creator
 * @property User $updater
 * @property Material $material
 */
class MaterialUsage extends Model {

	protected $table = 'material_usages';

	protected $fillable = [
		'material_id', 'used_by_id', 'datetime', 'place', 'reason'
	];

	protected $guarded = [
		'id', 'created_by', 'updated_by', 'created_at', 'updated_at'
	];

	protected $casts = [
		'datetime' => 'datetime:Y-m-d 00:00:00',
		'place'    => 'string',
		'reason'   => 'string'
	];

	protected $hidden = [
		'created_by', 'updated_by', 'created_at', 'updated_at', 'used_by_id'
	];

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);
	}

	public function setPlaceAttribute($place) {
		$this->attributes['place'] = (string)$place;
	}

	public function setReasonAttribute($reason) {
		$this->attributes['reason'] = (string)$reason;
	}

	public function setDatetimeAttribute($datetime) {
		$date                         = new Carbon($datetime);
		$this->attributes['datetime'] = $date->toDateTimeString();
	}

	public function usedBy() {
		return $this->belongsTo(User::class, 'used_by_id');
	}

	public function material() {
		return $this->belongsTo(Material::class, 'material_id');
	}

	public function creator() {
		return $this->belongsTo(User::class, 'created_by');
	}

	public function updater() {
		return $this->belongsTo(User::class, 'updated_by');
	}

}
