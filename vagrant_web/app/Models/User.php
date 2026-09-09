<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable as PassportAuthenticatable;
use Laravel\Passport\HasApiTokens;


/**
 * Class User
 *
 * @package App\Models
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property array $frontend_user_settings
 * @property int $id
 * @property bool $use_for_mat_usage
 * @property boolean $is_admin
 * @property Collection $foreignResourceIds
 * @property Collection $foreignMaterialIds
 *
 * @property Collection $resources
 */
class User extends Authenticatable implements PassportAuthenticatable {
	use HasFactory;
	use HasApiTokens;
	use Notifiable;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'name', 'email', 'password', 'is_admin'
	];

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password',
		'remember_token',
		'created_at',
		'updated_at',
		'frontend_user_settings',
		'email',
		'use_for_mat_usage',
		'is_admin'
	];

	protected $casts = [
		'is_admin'               => 'boolean',
		'frontend_user_settings' => 'array',
		'use_for_mat_usage'      => 'boolean'
	];

	public function foreignMaterialIds() {
		return $this->hasMany(ForeignMaterialId::class);
	}

	public function foreignResourceIds() {
		return $this->hasMany(ForeignResourceId::class);
	}

	public function isSuperAdmin() {
		return $this->getAttribute('is_admin') === TRUE;
	}

	public function resources() {
		return $this->hasMany(Resource::class, 'created_by');
	}
}
