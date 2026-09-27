<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Bundle
 *
 * @package App\Models
 * @property int $id
 * @property string $author
 * @property string installed_version
 * @property DateTime last_update
 * @property string $uuid
 * @property string $container_root
 * @property DateTime $created_at
 * @property DateTime $updated_at
 * @property string $name
 * @property string $description
 * @property bool $is_installed
 * @property bool $update_available
 * @property string $icon
 */
class Bundle extends Model {
	use HasFactory;
	use HasTimestamps;


	protected $fillable = [
		'name', 'description', 'installed_version', 'last_update', 'author', 'uuid', 'container_root', 'is_installed', 'update_available', 'icon',
	];

	protected $attributes = [
		'is_installed'      => FALSE,
		'installed_version' => NULL,
		'update_available'  => FALSE,
	];

	protected $casts = [
		'last_update'      => 'datetime',
		'is_installed'     => 'boolean',
		'update_available' => 'boolean',
		'icon'             => 'string',
	];

	public function foreignResourceIds() {
		return $this->hasMany(ForeignResourceId::class);
	}

	public function foreignMaterialds() {
		return $this->hasMany(ForeignMaterialId::class);
	}


}
