<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProfileTest extends TestCase {
	use RefreshDatabase;

	public function test_profile_requires_an_active_account_and_returns_only_profile_fields(): void {
		$this->getJson(route('api.v2.profile.show'))->assertUnauthorized();
		Passport::actingAs(User::factory()->create(['status' => UserStatus::Suspended]));
		$this->getJson(route('api.v2.profile.show'))->assertForbidden();

		$user = User::factory()->create();
		Passport::actingAs($user);
		$this->getJson(route('api.v2.profile.show'))
			->assertOk()
			->assertExactJson([
				'id' => $user->id,
				'name' => $user->name,
				'email' => $user->email,
				'created_at' => $user->created_at->toJSON(),
				'password_min_length' => config('password_policy.min_length'),
			]);
	}

	public function test_user_can_change_own_name_but_not_account_privileges(): void {
		$user = User::factory()->create();
		$other = User::factory()->create();
		Passport::actingAs($user);

		$this->patchJson(route('api.v2.profile.name.update'), [
			'name' => ' Neuer Name ', 'email' => 'hidden@example.test', 'is_admin' => true,
			'status' => UserStatus::Suspended->value, 'user_id' => $other->id,
		])->assertOk()->assertJsonPath('name', 'Neuer Name');
		$this->assertSame('Neuer Name', $user->fresh()->name);
		$this->assertSame($other->email, $other->fresh()->email);
		$this->assertSame(UserStatus::Active, $user->fresh()->status);
		$this->assertFalse($user->fresh()->is_admin);
		$this->patchJson(route('api.v2.profile.name.update'), ['name' => '   '])->assertUnprocessable();
	}

	public function test_email_change_requires_current_password_and_unique_address(): void {
		$user = User::factory()->create(['email' => 'alt@example.test', 'password' => Hash::make('bisheriges-passwort')]);
		User::factory()->create(['email' => 'belegt@example.test']);
		Passport::actingAs($user);

		$this->patchJson(route('api.v2.profile.email.update'), ['email' => 'neu@example.test', 'current_password' => 'falsch'])
			->assertUnprocessable()->assertJsonValidationErrors('current_password')
			->assertJsonPath('errors.current_password.0', 'Das aktuelle Passwort fehlt oder ist nicht korrekt.');
		$this->patchJson(route('api.v2.profile.email.update'), ['email' => 'BELEGT@example.test', 'current_password' => 'bisheriges-passwort'])
			->assertUnprocessable()->assertJsonValidationErrors('email');
		$this->assertSame('alt@example.test', $user->fresh()->email);

		$this->patchJson(route('api.v2.profile.email.update'), ['email' => ' NEU@example.test ', 'current_password' => 'bisheriges-passwort'])
			->assertOk()->assertJsonPath('email', 'neu@example.test');
		$this->assertSame('neu@example.test', $user->fresh()->email);
	}

	public function test_password_change_requires_current_password_confirmation_and_configured_length_and_revokes_tokens(): void {
		$user = User::factory()->create(['password' => Hash::make('bisheriges-passwort')]);
		Passport::actingAs($user);
		$payload = ['current_password' => 'bisheriges-passwort', 'password' => 'neues-langes-passwort', 'password_confirmation' => 'neues-langes-passwort'];

		$this->putJson(route('api.v2.profile.password.update'), [...$payload, 'current_password' => 'falsch'])
			->assertUnprocessable()->assertJsonValidationErrors('current_password');
		$this->putJson(route('api.v2.profile.password.update'), [...$payload, 'password_confirmation' => 'anders'])
			->assertUnprocessable()->assertJsonValidationErrors('password');
		$this->putJson(route('api.v2.profile.password.update'), [...$payload, 'password' => 'kurz', 'password_confirmation' => 'kurz'])
			->assertUnprocessable()->assertJsonValidationErrors('password')
			->assertJsonPath('errors.password.0', 'Das neue Passwort muss mindestens 12 Zeichen haben.');
		config()->set('password_policy.min_length', 24);
		$this->putJson(route('api.v2.profile.password.update'), $payload)
			->assertUnprocessable()->assertJsonValidationErrors('password');
		config()->set('password_policy.min_length', 12);
		$this->assertTrue(Hash::check('bisheriges-passwort', $user->fresh()->password));

		DB::table('oauth_access_tokens')->insert(['id' => 'profile-access-token', 'user_id' => $user->id, 'client_id' => 1, 'revoked' => false, 'created_at' => now(), 'updated_at' => now()]);
		DB::table('oauth_refresh_tokens')->insert(['id' => 'profile-refresh-token', 'access_token_id' => 'profile-access-token', 'revoked' => false, 'expires_at' => now()->addHour()]);
		$rememberToken = $user->getRememberToken();
		$this->putJson(route('api.v2.profile.password.update'), $payload)->assertOk()->assertJsonPath('password_changed', true);
		$this->assertTrue(Hash::check('neues-langes-passwort', $user->fresh()->password));
		$this->assertNotSame($rememberToken, $user->fresh()->getRememberToken());
		$this->assertTrue((bool)DB::table('oauth_access_tokens')->where('id', 'profile-access-token')->value('revoked'));
		$this->assertTrue((bool)DB::table('oauth_refresh_tokens')->where('id', 'profile-refresh-token')->value('revoked'));
	}
}
