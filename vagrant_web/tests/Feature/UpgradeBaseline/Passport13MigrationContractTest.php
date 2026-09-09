<?php

namespace Tests\Feature\UpgradeBaseline;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class Passport13MigrationContractTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_populated_legacy_client_is_upgraded_without_losing_its_identity_or_grants(): void
    {
        $migration = require database_path('migrations/2026_09_09_000001_upgrade_oauth_clients_to_passport_13.php');
        $migration->down();

        $user = User::factory()->create();
        $plainSecret = 'legacy-client-secret-for-migration-test';

        DB::table('oauth_clients')->insert([
            'id' => 42,
            'user_id' => $user->id,
            'name' => 'Legacy Password Client',
            'secret' => $plainSecret,
            'provider' => 'users',
            'redirect' => 'https://client.example/callback,https://client.example/second',
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('oauth_access_tokens')->insert([
            'id' => 'legacy-access-token',
            'user_id' => $user->id,
            'client_id' => 42,
            'name' => null,
            'scopes' => '[]',
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $migration->up();

        $row = DB::table('oauth_clients')->where('id', '42')->first();
        $client = (new ClientRepository())->find('42');

        $this->assertNotNull($row);
        $this->assertNotNull($client);
        $this->assertSame('42', (string) $row->id);
        $this->assertSame(User::class, $row->owner_type);
        $this->assertSame($user->id, $row->owner_id);
        $this->assertTrue(Hash::check($plainSecret, $row->secret));
        $this->assertNotSame($plainSecret, $row->secret);
        $this->assertSame(
            ['https://client.example/callback', 'https://client.example/second'],
            $client->redirect_uris
        );
        $this->assertContains('password', $client->grant_types);
        $this->assertContains('refresh_token', $client->grant_types);
        $this->assertSame('42', (string) DB::table('oauth_access_tokens')->value('client_id'));
        $this->assertFalse(Schema::hasColumn('oauth_clients', 'password_client'));

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'password',
            'client_id' => '42',
            'client_secret' => $plainSecret,
            'username' => $user->email,
            'password' => 'secret',
            'scope' => '',
        ]);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $response->assertJsonStructure([
            'token_type', 'expires_in', 'access_token', 'refresh_token',
        ]);
    }
}
