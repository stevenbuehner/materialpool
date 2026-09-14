<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserFactory extends Factory {
	protected $model = User::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {

		return [
			'name'              => $this->faker->name(),
			'email'             => $this->faker->unique()->safeEmail(),
			'password'          => bcrypt('secret'),
			'status'            => UserStatus::Active,
			'remember_token'    => Str::random(10),
			'use_for_mat_usage' => $this->faker->boolean()
		];
	}

	public function configure() {
		return $this->afterCreating(function (User $user): void {
			if (!$user->is_admin && Schema::hasTable('roles')) {
				$defaultRole = Role::query()->where('name', SystemPermissions::DEFAULT_GROUP)->first();
				if ($defaultRole) {
					$user->assignRole($defaultRole);
				}
			}
		});
	}

}
