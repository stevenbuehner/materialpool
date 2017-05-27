<?php

namespace Modules\MaterialGrabber\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GrabberConfigValue
 *
 * @property string $name
 * @property mixed  $value
 * @property int    $grabber_id
 */
class GrabberConfigValue extends Model {
	protected $table    = 'grabber_config_values';
	protected $fillable = ['name', 'value', 'grabber_id'];

	public function grabber() {
		return $this->belongsTo(GrabberConfig::class, 'grabber_id');
	}
}
