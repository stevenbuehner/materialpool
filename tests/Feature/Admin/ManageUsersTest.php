<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManageUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_installation_creates_an_active_global_admin_who_can_log_in(): void
    {
        $this->artisan('users:manage', ['action' => 'create', '--first-admin' => true])
            ->expectsQuestion('Name', 'Erste Administratorin')
            ->expectsQuestion('E-Mail', 'ADMIN@example.test')
            ->expectsQuestion('Passwort (mindestens 12 Zeichen)', 'ein-langes-testpasswort')
            ->expectsQuestion('Passwort bestätigen', 'ein-langes-testpasswort')
            ->assertExitCode(0);

        $admin = User::query()->sole();
        $this->assertSame('admin@example.test', $admin->email);
        $this->assertSame(UserStatus::Active, $admin->status);
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue(Hash::check('ein-langes-testpasswort', $admin->password));

        $this->post('/login', ['email' => 'admin@example.test', 'password' => 'ein-langes-testpasswort'])
            ->assertRedirect('/home');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_later_user_receives_the_default_group_without_global_admin_rights(): void
    {
        User::factory()->create(['is_admin' => true]);

        $this->artisan('users:manage', ['action' => 'create'])
            ->expectsQuestion('Name', 'Standardnutzerin')
            ->expectsQuestion('E-Mail', 'standard@example.test')
            ->expectsQuestion('Passwort (mindestens 12 Zeichen)', 'ein-anderes-testpasswort')
            ->expectsQuestion('Passwort bestätigen', 'ein-anderes-testpasswort')
            ->assertExitCode(0);

        $user = User::query()->where('email', 'standard@example.test')->sole();
        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->hasRole(SystemPermissions::DEFAULT_GROUP));
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_short_password_option_creates_first_admin_with_four_character_password(): void
    {
        $this->artisan('users:manage', ['action' => 'create', '--first-admin' => true, '--short-password' => true])
            ->expectsQuestion('Name', 'Administratorin')
            ->expectsQuestion('E-Mail', 'admin@example.test')
            ->expectsQuestion('Passwort (mindestens 4 Zeichen)', 'abcd')
            ->expectsQuestion('Passwort bestätigen', 'abcd')
            ->assertExitCode(0);

        $admin = User::query()->sole();
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue(Hash::check('abcd', $admin->password));

        $this->post('/login', ['email' => 'admin@example.test', 'password' => 'abcd'])
            ->assertRedirect('/home');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_short_password_option_retries_when_password_has_fewer_than_four_characters(): void
    {
        $this->artisan('users:manage', ['action' => 'create', '--first-admin' => true, '--short-password' => true])
            ->expectsQuestion('Name', 'Administrator')
            ->expectsQuestion('E-Mail', 'admin@example.test')
            ->expectsQuestion('Passwort (mindestens 4 Zeichen)', 'abc')
            ->expectsQuestion('Passwort bestätigen', 'abc')
            ->expectsQuestion('Name', 'Administratorin')
            ->expectsQuestion('E-Mail', 'admin@example.test')
            ->expectsQuestion('Passwort (mindestens 4 Zeichen)', 'abcd')
            ->expectsQuestion('Passwort bestätigen', 'abcd')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('Administratorin', User::query()->sole()->name);
    }

    public function test_short_password_option_is_rejected_without_first_admin(): void
    {
        $this->artisan('users:manage', ['action' => 'create', '--short-password' => true])
            ->assertExitCode(2);

        $this->artisan('users:manage', ['action' => 'update', '--short-password' => true])
            ->assertExitCode(2);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_existing_user_can_change_email_and_password_without_changing_status_or_role(): void
    {
        $user = User::factory()->create([
            'email' => 'alt@example.test',
            'password' => Hash::make('bisheriges-passwort'),
            'status' => UserStatus::Suspended,
        ]);

        $this->artisan('users:manage', ['action' => 'update'])
            ->expectsQuestion('Aktuelle E-Mail des Benutzers', 'alt@example.test')
            ->expectsQuestion('Name', 'Neuer Name')
            ->expectsQuestion('E-Mail', 'NEU@example.test')
            ->expectsQuestion('Neues Passwort (leer lassen für unverändert)', 'neues-langes-passwort')
            ->expectsQuestion('Neues Passwort bestätigen', 'neues-langes-passwort')
            ->assertExitCode(0);

        $user->refresh();
        $this->assertSame('Neuer Name', $user->name);
        $this->assertSame('neu@example.test', $user->email);
        $this->assertFalse(Hash::check('bisheriges-passwort', $user->password));
        $this->assertTrue(Hash::check('neues-langes-passwort', $user->password));
        $this->assertSame(UserStatus::Suspended, $user->status);
        $this->assertTrue($user->hasRole(SystemPermissions::DEFAULT_GROUP));
    }

    public function test_first_admin_cannot_be_created_when_users_already_exist(): void
    {
        User::factory()->create();

        $this->artisan('users:manage', ['action' => 'create', '--first-admin' => true])
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_update_keeps_the_password_when_new_password_is_empty(): void
    {
        $user = User::factory()->create(['email' => 'bestehend@example.test']);
        $passwordHash = $user->password;

        $this->artisan('users:manage', ['action' => 'update'])
            ->expectsQuestion('Aktuelle E-Mail des Benutzers', 'bestehend@example.test')
            ->expectsQuestion('Name', $user->name)
            ->expectsQuestion('E-Mail', $user->email)
            ->expectsQuestion('Neues Passwort (leer lassen für unverändert)', '')
            ->assertExitCode(0);

        $this->assertSame($passwordHash, $user->fresh()->password);
    }

    public function test_duplicate_email_does_not_change_an_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'alt@example.test']);
        $originalName = $user->name;
        User::factory()->create(['email' => 'belegt@example.test']);

        $this->artisan('users:manage', ['action' => 'update'])
            ->expectsQuestion('Aktuelle E-Mail des Benutzers', 'alt@example.test')
            ->expectsQuestion('Name', 'Neuer Name')
            ->expectsQuestion('E-Mail', 'belegt@example.test')
            ->expectsQuestion('Neues Passwort (leer lassen für unverändert)', '')
            ->assertExitCode(2);

        $user->refresh();
        $this->assertSame('alt@example.test', $user->email);
        $this->assertSame($originalName, $user->name);
    }

    public function test_invalid_first_admin_password_restarts_input_and_creates_only_valid_admin(): void
    {
        $this->artisan('users:manage', ['action' => 'create', '--first-admin' => true])
            ->expectsQuestion('Name', 'Administrator')
            ->expectsQuestion('E-Mail', 'admin@example.test')
            ->expectsQuestion('Passwort (mindestens 12 Zeichen)', 'kurz')
            ->expectsQuestion('Passwort bestätigen', 'kurz')
            ->expectsQuestion('Name', 'Administratorin')
            ->expectsQuestion('E-Mail', 'admin@example.test')
            ->expectsQuestion('Passwort (mindestens 12 Zeichen)', 'ein-langes-testpasswort')
            ->expectsQuestion('Passwort bestätigen', 'ein-langes-testpasswort')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('Administratorin', User::query()->sole()->name);
    }

    public function test_invalid_password_for_later_user_still_exits_without_creating_a_user(): void
    {
        User::factory()->create(['is_admin' => true]);

        $this->artisan('users:manage', ['action' => 'create'])
            ->expectsQuestion('Name', 'Standardnutzer')
            ->expectsQuestion('E-Mail', 'standard@example.test')
            ->expectsQuestion('Passwort (mindestens 12 Zeichen)', 'kurz')
            ->expectsQuestion('Passwort bestätigen', 'kurz')
            ->assertExitCode(2);

        $this->assertDatabaseCount('users', 1);
    }
}
