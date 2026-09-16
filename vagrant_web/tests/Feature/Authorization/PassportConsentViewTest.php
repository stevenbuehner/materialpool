<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class PassportConsentViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_sees_an_escaped_authorization_code_consent_view(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $client = (new ClientRepository())->createAuthorizationCodeGrantClient(
            'Calendar <script>alert(1)</script>',
            ['https://client.example/callback']
        );

        $response = $this->actingAs($user)->get(route('passport.authorizations.authorize', [
            'response_type' => 'code',
            'client_id' => $client->getKey(),
            'redirect_uri' => 'https://client.example/callback',
            'state' => 'known-state',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('auth.oauth.authorize')
            ->assertSeeText(__('oauth.authorize.heading'))
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('name="auth_token"', false);
    }

    public function test_suspended_user_is_logged_out_before_an_authorization_consent_view_is_rendered(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);
        $client = (new ClientRepository())->createAuthorizationCodeGrantClient(
            'Calendar integration',
            ['https://client.example/callback']
        );

        $this->actingAs($user)
            ->get(route('passport.authorizations.authorize', [
                'response_type' => 'code',
                'client_id' => $client->getKey(),
                'redirect_uri' => 'https://client.example/callback',
            ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email', __('auth.account_inactive'));

        $this->assertGuest();
    }

    public function test_active_user_can_approve_a_rendered_authorization_code_consent_request_once(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $client = (new ClientRepository())->createAuthorizationCodeGrantClient(
            'Calendar integration',
            ['https://client.example/callback']
        );

        $this->actingAs($user)->get(route('passport.authorizations.authorize', [
            'response_type' => 'code',
            'client_id' => $client->getKey(),
            'redirect_uri' => 'https://client.example/callback',
            'state' => 'known-state',
        ]))->assertViewIs('auth.oauth.authorize');

        $authToken = $this->app['session.store']->get('authToken');

        $this->assertIsString($authToken);

        $this->post(route('passport.authorizations.approve'), ['auth_token' => $authToken])
            ->assertRedirectContains('https://client.example/callback?code=')
            ->assertRedirectContains('state=known-state');

        $this->assertDatabaseCount('oauth_auth_codes', 1);
    }
}
