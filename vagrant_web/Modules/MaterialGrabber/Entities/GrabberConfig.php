<?php

namespace Modules\MaterialGrabber\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GrabberConfig
 *
 * @property int       $id
 * @property bool      $is_active
 * @property string    $description
 * @property string    $author
 * @property string    $name
 * @property \DateTime $last_complete_run
 */
class GrabberConfig extends Model {
	protected $table = 'grabber_grabbers';

	protected $cachedConfigValues = [];

	protected $attributes = [
		'is_active' => TRUE
	];

	protected $guarded = [];

	protected $casts = [
		'is_active'         => 'boolean',
		'author'            => 'string',
		'name'              => 'string',
		'description'       => 'string',
		'last_complete_run' => 'datetime'
	];

	public function links() {
		return $this->hasMany(Link::class, 'grabber_id');
	}

	public function removeConfigValueByName($name) {
		$this->configValues()->where(['name' => $name])->delete();
	}

	public function configValues() {
		return $this->hasMany(GrabberConfigValue::class, 'grabber_id');
	}

	/**
	 * @param      $name
	 * @param null $default
	 * @return string|NULL
	 */
	public function getConfigValueByName($name, $default = NULL) {

		// Use Cache if possible
		if ($this->hasCachedConfigValue($name)) {
			$configValue = $this->getCachedConfigValue($name);
		} else {
			$configValue = $this->configValues()->where(['name' => $name])->first();
		}


		if ($configValue) {
			$this->setCachedConfigValue($name, $configValue);

			return $configValue->value;
		} else {
			return $default;
		}
	}

	/**
	 * @param $key
	 * @return bool
	 */
	protected function hasCachedConfigValue($key) {
		return isset($this->cachedConfigValues[$key]);
	}

	/**
	 * @param $key
	 * @return GrabberConfigValue
	 */
	protected function getCachedConfigValue($key) {
		return $this->cachedConfigValues[$key];
	}

	protected function setCachedConfigValue($key, GrabberConfigValue $value) {
		$this->cachedConfigValues[$key] = $value;
	}

	public function setConfigValue($name, $value) {
		$configValue = GrabberConfigValue::updateOrCreate(
			['grabber_id' => $this->getAttribute('id'), 'name' => $name],
			['value' => $value]
		);

		$this->setCachedConfigValue($name, $configValue);

	}
}
