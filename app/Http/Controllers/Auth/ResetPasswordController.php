<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller {
	/*
	|--------------------------------------------------------------------------
	| Password Reset Controller
	|--------------------------------------------------------------------------
	|
	| This controller is responsible for handling password reset requests
	| and uses a simple trait to include this behavior. You're free to
	| explore this trait and override any methods you wish to tweak.
	|
	*/

	use ResetsPasswords;

	/**
	 * Where to redirect users after resetting their password.
	 *
	 * @var string
	 */
	protected $redirectTo = '/home';

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('guest');
	}

	protected function rules(): array {
		return [
			'token' => ['required'],
			'email' => ['required', 'email'],
			'password' => ['required', 'string', 'min:'.config('password_policy.min_length'), 'confirmed'],
		];
	}

	protected function resetPassword($user, $password): void {
		$user->password = Hash::make($password);
		$user->setRememberToken(Str::random(60));
		if ($user->status === UserStatus::Invited) {
			$user->status = UserStatus::Active;
		}
		$user->save();

		event(new PasswordReset($user));

		if ($user->isActive()) {
			$this->guard()->login($user);
		}
	}
}
