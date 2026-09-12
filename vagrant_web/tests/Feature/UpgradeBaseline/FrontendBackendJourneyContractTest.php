<?php

namespace Tests\Feature\UpgradeBaseline;

use App\Models\Text;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class FrontendBackendJourneyContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_read_options_save_settings_and_reload_them(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        $this->getJson(route('api.v1.general.options'))
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['user', 'server' => ['max_upload'], 'systemname']);

        $settings = [
            'materialPresets' => [
                ['title' => 'Synthetische Release-Prüfung'],
            ],
        ];

        $this->postJson(route('api.v1.users.store_settings', ['user' => $user]), [
            'data' => $settings,
        ])->assertOk();

        $this->assertSame($settings, $user->fresh()->frontend_user_settings);
    }

    public function test_settings_endpoint_returns_a_real_validation_error(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, []);

        $this->postJson(route('api.v1.users.store_settings', ['user' => $user]), [
            'data' => 'not-an-array',
        ])->assertUnprocessable()->assertJsonValidationErrors('data');
    }

    public function test_private_resource_returns_a_real_permission_error_for_another_user(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $resource = Text::factory()->create([
            'created_by' => $owner->id,
            'is_public' => false,
        ]);
        Passport::actingAs($viewer, []);

        $this->getJson(route('api.v1.resources.show', ['resource' => $resource]))
            ->assertForbidden();
    }
}
