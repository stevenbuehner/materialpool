<?php

namespace Modules\MaterialGrabber\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Link
 *
 * @package Modules\MaterialGrabber\Entities
 * @property int         priority
 * @property bool        is_index
 * @property int         status
 * @property \DateTime   last_check
 * @property array       options
 * @property string      url
 * @property string|null file_path
 * @property string|null md5_cache
 * @property int         grabber_id
 */
class Link extends Model {

	/* When changing stati, also change setStatus()*/
	static $STATUS_UNKNOWN              = 0;
	static $STATUS_WAITING              = 1;
	static $STATUS_FINISHED_SUCCESSFULL = 4;
	static $STATUS_FINSIHED_WITH_ERRORS = 5;
	static $STATUS_DELETED              = 6;

	protected $table      = 'grabber_links';
	protected $fillable   = ['priority', 'url', 'file_path', 'is_index', 'status', 'last_check', 'md5_cache'];
	protected $attributes = ['options' => []];
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

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function grabber() {
		return $this->hasOne(GrabberConfig::class, 'grabber_id');
	}

	public function material() {
		return $this->hasOne(\App\Models\Material::class, 'material_id');
	}

	public function addOption($key, $value) {
		$options       = $this->getAttribute('options');
		$options[$key] = $value;
		$this->setAttribute('options', $options);
	}

}
