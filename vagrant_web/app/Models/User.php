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
 * @property string  $name
 * @property string  $email
 * @property string  $password
 * @property string  $remember_token
 * @property int     $id
 * @property boolean $is_admin
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
		'name', 'email', 'password', 'is_admin'
	];

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password', 'remember_token',
	];

	protected $casts = [
		'is_admin' => 'boolean'
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

	public function foreignMaterialIds() {
		return $this->hasMany(ForeignMaterialId::class);
	}

	public function isSuperAdmin() {
		return $this->getAttribute('is_admin') === TRUE;
	}
}
