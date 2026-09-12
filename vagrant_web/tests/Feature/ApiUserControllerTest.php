<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_search_accepts_an_empty_search_parameter(): void
    {
        $this->withoutDeprecationHandling();

        $user = User::factory()->create(['use_for_mat_usage' => true]);
        Passport::actingAs($user, []);

        $this->getJson(route('api.v2.users.find', ['s' => '']))
            ->assertOk()
            ->assertJsonPath('0.id', $user->id);
    }
}
