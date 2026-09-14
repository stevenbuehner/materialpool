<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\UserInvitation;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase {
	use RefreshDatabase;

	public function test_global_admin_can_invite_user_with_default_group(): void {
		Notification::fake();
		$admin = User::factory()->create(['is_admin' => true]);
		Passport::actingAs($admin);

		$response = $this->postJson(route('api.v2.admin.users.store'), [
			'name' => 'Neue Nutzerin',
			'email' => 'neu@example.test',
		]);

		$response->assertCreated()->assertJsonPath('user.status', UserStatus::Invited->value);
		$user = User::where('email', 'neu@example.test')->firstOrFail();
		$this->assertTrue($user->hasRole(SystemPermissions::DEFAULT_GROUP));
		Notification::assertSentTo($user, UserInvitation::class);
	}

	public function test_non_admin_cannot_access_admin_api(): void {
		Passport::actingAs(User::factory()->create());
		$this->getJson(route('api.v2.admin.users.index'))->assertNotFound();
	}

	public function test_last_active_admin_cannot_be_demoted(): void {
		$admin = User::factory()->create(['is_admin' => true]);
		Passport::actingAs($admin);

		$this->patchJson(route('api.v2.admin.users.update', $admin), ['is_admin' => false])
			->assertUnprocessable();
		$this->assertTrue($admin->fresh()->is_admin);
	}

	public function test_group_with_users_cannot_be_deleted(): void {
		$admin = User::factory()->create(['is_admin' => true]);
		$user = User::factory()->create();
		$group = Role::findByName(SystemPermissions::DEFAULT_GROUP);
		$this->assertTrue($user->hasRole($group));
		Passport::actingAs($admin);

		$this->deleteJson(route('api.v2.admin.groups.destroy', $group))->assertConflict();
	}

	public function test_group_api_rejects_unknown_permission_codes(): void {
		$admin = User::factory()->create(['is_admin' => true]);
		Passport::actingAs($admin);

		$this->postJson(route('api.v2.admin.groups.store'), [
			'name' => 'Ungültige Gruppe',
			'permissions' => ['invented.permission'],
		])->assertUnprocessable();

		$this->assertDatabaseMissing('roles', ['name' => 'Ungültige Gruppe']);
	}

	public function test_user_cannot_write_another_users_settings(): void {
		$user = User::factory()->create();
		$other = User::factory()->create();
		Passport::actingAs($user);

		$this->postJson(route('api.v1.users.store_settings', $other), ['data' => ['theme' => 'dark']])
			->assertForbidden();
	}
}
