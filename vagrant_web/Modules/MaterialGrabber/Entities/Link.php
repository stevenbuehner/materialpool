<?php

namespace Modules\MaterialGrabber\Entities;

use Illuminate\Database\Eloquent\Model;

class Link extends Model {
	protected $table    = 'grabber_links';
	protected $fillable = ['priority', 'url', 'file_path', 'is_index', 'status', 'last_check', 'md5_cache'];
	protected $casts    = [
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
		return $this->hasOne(Grabber::class, 'grabber_id');
	}

	public function material() {
		return $this->hasOne(\App\Models\Material::class, 'material_id');
	}

}
