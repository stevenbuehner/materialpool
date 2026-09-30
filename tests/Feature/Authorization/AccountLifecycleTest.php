<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AccountLifecycleTest extends TestCase {
	use RefreshDatabase;

	public function test_active_user_can_log_in_access_a_protected_page_and_log_out_with_json_sessions(): void {
		$user = User::factory()->create([
			'status' => UserStatus::Active,
			'password' => Hash::make('secret'),
		]);

		$this->post('/login', ['email' => $user->email, 'password' => 'secret'])
			->assertRedirect('/home');
		$this->assertAuthenticatedAs($user);

		$this->get('/home')->assertOk();

		$this->post('/logout')->assertRedirect('/');
		$this->assertGuest();
	}

	public function test_invited_and_suspended_users_cannot_log_in_or_use_authenticated_api(): void {
		$invited = User::factory()->create(['status' => UserStatus::Invited, 'password' => Hash::make('secret')]);
		$this->post('/login', ['email' => $invited->email, 'password' => 'secret'])
			->assertSessionHasErrors('email');
		$this->assertGuest();
		$this->assertNull((new User())->findForPassport($invited->email));

		$suspended = User::factory()->create(['status' => UserStatus::Suspended]);
		Passport::actingAs($suspended);
		$this->getJson(route('api.v1.general.options'))->assertForbidden();
	}

	public function test_invitation_reset_activates_invited_but_never_suspended_user(): void {
		$invited = User::factory()->create(['status' => UserStatus::Invited]);
		$this->post(route('password.update'), [
			'token' => Password::broker()->createToken($invited),
			'email' => $invited->email,
			'password' => 'new-long-password',
			'password_confirmation' => 'new-long-password',
		])->assertRedirect('/home');
		$this->assertSame(UserStatus::Active, $invited->fresh()->status);

		$this->post('/logout');
		$suspended = User::factory()->create(['status' => UserStatus::Suspended]);
		$this->post(route('password.update'), [
			'token' => Password::broker()->createToken($suspended),
			'email' => $suspended->email,
			'password' => 'another-long-password',
			'password_confirmation' => 'another-long-password',
		])->assertRedirect('/home');
		$this->assertSame(UserStatus::Suspended, $suspended->fresh()->status);
		$this->assertGuest();
	}

	public function test_suspending_user_revokes_access_and_refresh_tokens(): void {
		$admin = User::factory()->create(['is_admin' => true]);
		$user = User::factory()->create();
		DB::table('oauth_access_tokens')->insert([
			'id' => 'access-token-test', 'user_id' => $user->id, 'client_id' => 1,
			'revoked' => false, 'created_at' => now(), 'updated_at' => now(),
		]);
		DB::table('oauth_refresh_tokens')->insert([
			'id' => 'refresh-token-test', 'access_token_id' => 'access-token-test',
			'revoked' => false, 'expires_at' => now()->addHour(),
		]);
		Passport::actingAs($admin);

		$this->patchJson(route('api.v2.admin.users.update', $user), ['status' => UserStatus::Suspended->value])
			->assertOk();

		$this->assertTrue((bool) DB::table('oauth_access_tokens')->where('id', 'access-token-test')->value('revoked'));
		$this->assertTrue((bool) DB::table('oauth_refresh_tokens')->where('id', 'refresh-token-test')->value('revoked'));
	}
}
