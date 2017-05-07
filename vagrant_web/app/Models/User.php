<?php

namespace App\Models;

use Backpack\Base\app\Notifications\ResetPasswordNotification as ResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;


/**
 * Class User
 *
 * @package App\Models
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 */
class User extends Authenticatable {
	use Notifiable;
	use HasApiTokens;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'name', 'email', 'password',
	];

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password', 'remember_token',
	];

	/**
	 * Send the password reset notification.
	 *
	 * @param  string $token
	 * @return void
	 */
	public function sendPasswordResetNotification($token) {
		$this->notify(new ResetPasswordNotification($token));
	}

	/**
	 * Get the foreignInstances that belong to this user
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function foreignInstance() {
		return $this->hasMany(ForeignInstance::class);
	}

	public function isSuperAdmin() {
		return $this->getAttribute('is_admin') === TRUE;
	}
}
