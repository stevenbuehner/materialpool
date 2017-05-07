<?php

namespace Modules\MaterialGrabber\Entities;

use Illuminate\Database\Eloquent\Model;

class Grabber extends Model {
	protected $table = 'grabber_grabbers';

	protected $attributes = [
		'is_active' => TRUE
	];

	protected $casts = [
		'is_active'         => 'boolean',
		'author'            => 'string',
		'name'              => 'string',
		'description'       => 'string',
		'last_complete_run' => 'datetime'
	];

	public function configValues() {
		return $this->hasMany(GrabberConfigValue::class, 'grabber_id');
	}

	public function links() {
		return $this->hasMany(Link::class, 'grabber_id');
	}
}
