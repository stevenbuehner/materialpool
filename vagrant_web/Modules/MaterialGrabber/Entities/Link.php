<?php

namespace Modules\MaterialGrabber\Entities;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Link
 *
 * @package Modules\MaterialGrabber\Entities
 * @property int         $id
 * @property int         $priority
 * @property bool        $is_index
 * @property int         $status
 * @property \DateTime   $last_check
 * @property array       $options
 * @property string      $url
 * @property string|null $file_path
 * @property string|null $md5_cache
 * @property int         $grabber_id
 * @property int         $resource_id
 */
class Link extends Model {

	/* When changing stati, also change setStatus()*/
	static $STATUS_UNKNOWN              = 0;
	static $STATUS_WAITING              = 1;
	static $STATUS_FINISHED_SUCCESSFULL = 4;
	static $STATUS_FINSIHED_WITH_ERRORS = 5;
	static $STATUS_DELETED              = 6;

	protected $table      = 'grabber_links';
	protected $fillable   = ['grabber_id', 'priority', 'url', 'file_path', 'is_index', 'status', 'last_check', 'md5_cache'];
	protected $attributes = [
		'priority' => 100
	];
	protected $casts      = [
		'priority'   => 'integer',
		'is_index'   => 'boolean',
		'status'     => 'integer',
		'last_check' => 'datetime',
		'options'    => 'array'
	];

	public function parent() {
		return $this->hasOne(Link::class, 'parent_id');
	}

	public function children() {
		return $this->hasMany(Link::class, 'parent_id');
	}

	public function grabber() {
		return $this->belongsTo(GrabberConfig::class, 'grabber_id');
	}

	public function material() {
		return $this->belongsTo(\App\Models\Material::class, 'material_id');
	}

	public function resource() {
		return $this->belongsTo(Resource::class);
	}

	public function addOption($key, $value) {
		$options       = $this->getAttribute('options');
		$options[$key] = $value;
		$this->setAttribute('options', $options);
	}

	public function getOption($key, $default = NULL) {
		$options = $this->getAttribute('options');

		if (isset($options[$key])) {
			return $options[$key];
		}

		return $default;
	}

}
