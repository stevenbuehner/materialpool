<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Passport\Passport;

class ProfileController extends Controller {
	public function show(Request $request): array {
		return $this->profile($request);
	}

	public function updateName(Request $request): array {
		if (is_string($request->input('name'))) {
			$request->merge(['name' => trim($request->input('name'))]);
		}
		$validated = $request->validate(
			['name' => ['required', 'string', 'max:255']],
			['name.required' => __('pool.Profile-name-required'), 'name.string' => __('pool.Profile-name-required'), 'name.max' => __('pool.Profile-name-too-long')]
		);
		$user = $request->user();
		$user->name = $validated['name'];
		$user->save();

		return $this->profile($request);
	}

	public function updateEmail(Request $request): array {
		$user = $request->user();
		if (is_string($request->input('email'))) {
			$request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
		}
		$validated = $request->validate([
			'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
			'current_password' => ['required', 'current_password:api'],
		], [
			'email.required' => __('pool.Profile-email-invalid'),
			'email.email' => __('pool.Profile-email-invalid'),
			'email.max' => __('pool.Profile-email-invalid'),
			'email.unique' => __('pool.Profile-email-taken'),
			'current_password.required' => __('pool.Profile-current-password-invalid'),
			'current_password.current_password' => __('pool.Profile-current-password-invalid'),
		]);
		$user->email = $validated['email'];
		$user->save();
		Log::notice('profile.email.updated', ['user_id' => $user->id]);

		return $this->profile($request);
	}

	public function updatePassword(Request $request): JsonResponse {
		$validated = $request->validate([
			'current_password' => ['required', 'current_password:api'],
			'password' => ['required', 'string', 'min:'.config('password_policy.min_length'), 'confirmed'],
		], [
			'current_password.required' => __('pool.Profile-current-password-invalid'),
			'current_password.current_password' => __('pool.Profile-current-password-invalid'),
			'password.required' => __('pool.Profile-new-password-required'),
			'password.string' => __('pool.Profile-new-password-required'),
			'password.min' => __('pool.Profile-password-min', ['count' => config('password_policy.min_length')]),
			'password.confirmed' => __('pool.Profile-password-mismatch'),
		]);
		$user = $request->user();
		DB::transaction(function () use ($user, $validated): void {
			$user->password = Hash::make($validated['password']);
			$user->setRememberToken(Str::random(60));
			$user->save();

			Passport::tokenModel()::query()->where('user_id', $user->getAuthIdentifier())->get()->each(function ($token): void {
				$token->refreshToken()->update(['revoked' => TRUE]);
				$token->revoke();
			});
		});
		Log::notice('profile.password.updated', ['user_id' => $user->id]);

		return response()->json(['password_changed' => TRUE]);
	}

	private function profile(Request $request): array {
		$user = $request->user();

		return [
			'id' => $user->id,
			'name' => $user->name,
			'email' => $user->email,
			'created_at' => $user->created_at,
			'password_min_length' => config('password_policy.min_length'),
		];
	}
}
