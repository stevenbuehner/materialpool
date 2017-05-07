<?php

namespace Modules\MaterialGrabber\Entities;

use Illuminate\Database\Eloquent\Model;

class GrabberConfigValue extends Model {
	protected $table    = 'grabber_config_values';
	protected $fillable = ['name', 'value'];

	public function grabber() {
		return $this->belongsTo(Grabber::class, 'grabber_id');
	}
}
