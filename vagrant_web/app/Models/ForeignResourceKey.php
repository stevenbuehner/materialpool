<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ForeignResourceKey
 *
 * @property int remote_id;
 * @property int foreign_instance_id;
 * @property int resource_id;
 */
class ForeignResourceKey extends Model {

	use CompositeKeysTrait;

	protected $casts      = [
		'remote_id'           => 'int',
		'foreign_instance_id' => 'int',
		'resource_id'         => 'int'
	];
	protected $guarded    = [];
	protected $primaryKey = [
		'remote_id',
		'foreign_instance_id'
	];

	protected $hidden = [
		'created_at', 'updated_at'
	];

	public static function findOneWhere($foreignInstanceId, $resourceId = NULL, $remote_id = NULL) {
		/** @var Builder $builder */
		$builder = self::where('foreign_instance_id', $foreignInstanceId);

		if ($remote_id !== NULL) {
			$builder->where('remote_id', '=', $remote_id);
		}

		if ($resourceId !== NULL) {
			$builder->where('resource_id', '=', $resourceId);
		}

		return $builder->first();
	}

	public function instance() {
		return $this->belongsTo(ForeignInstance::class);
	}

	public function resource() {
		return $this->belongsTo(Resource::class);
	}

}
