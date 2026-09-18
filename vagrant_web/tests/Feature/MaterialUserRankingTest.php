<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use App\Services\MaterialHandling\MaterialUserRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class MaterialUserRankingTest extends TestCase {
	use RefreshDatabase;

	public function test_user_can_create_and_update_a_personal_ranking(): void {
		$user = User::factory()->create();
		$material = Material::factory()->create(['created_by' => $user->id, 'modified_by' => $user->id, 'rating' => 8]);
		Passport::actingAs($user);

		$response = $this->putJson(route('api.v1.materials.user-ranking.update', $material), ['rating' => 13])
			->assertOk()
			->assertJsonPath('rating', 13)
			->assertJsonPath('user_rating', 13);
		$this->assertNotNull($response->json('user_rating_updated_at'));
		$this->assertDatabaseHas('material_user_ranking', ['material_id' => $material->id, 'user_id' => $user->id, 'rating' => 13]);

		$this->putJson(route('api.v1.materials.user-ranking.update', $material), ['rating' => 17])
			->assertOk()
			->assertJsonPath('rating', 17)
			->assertJsonPath('user_rating', 17);
		$this->assertDatabaseCount('material_user_ranking', 1);
	}

	public function test_api_rankings_are_averaged_and_the_last_reset_keeps_the_current_default(): void {
		$owner = User::factory()->create();
		$firstRater = User::factory()->create(['is_admin' => true]);
		$secondRater = User::factory()->create(['is_admin' => true]);
		$material = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id, 'rating' => 4]);

		Passport::actingAs($firstRater);
		$this->putJson(route('api.v1.materials.user-ranking.update', $material), ['rating' => 10])
			->assertOk()
			->assertJsonPath('rating', 10);

		Passport::actingAs($secondRater);
		$this->putJson(route('api.v1.materials.user-ranking.update', $material), ['rating' => 11])
			->assertOk()
			->assertJsonPath('rating', 11);
		$this->assertSame(11, $material->fresh()->rating);

		Passport::actingAs($firstRater);
		$this->deleteJson(route('api.v1.materials.user-ranking.destroy', $material))
			->assertOk()
			->assertJsonPath('user_rating', null)
			->assertJsonPath('rating', 11);
		$this->assertSame(11, $material->fresh()->rating);

		Passport::actingAs($secondRater);
		$this->deleteJson(route('api.v1.materials.user-ranking.destroy', $material))
			->assertOk()
			->assertJsonPath('user_rating', null)
			->assertJsonPath('rating', 11);
		$this->assertSame(11, $material->fresh()->rating);
		$this->assertDatabaseCount('material_user_ranking', 0);
	}

	public function test_ranking_endpoint_requires_a_valid_integer_rating(): void {
		$user = User::factory()->create();
		$material = Material::factory()->create(['created_by' => $user->id, 'modified_by' => $user->id]);

		$this->putJson(route('api.v1.materials.user-ranking.update', $material), ['rating' => 10])->assertUnauthorized();

		Passport::actingAs($user);
		$this->putJson(route('api.v1.materials.user-ranking.update', $material), ['rating' => 21])
			->assertUnprocessable()
			->assertJsonValidationErrors(['rating']);
		$this->putJson(route('api.v1.materials.user-ranking.update', $material), ['rating' => 10.5])
			->assertUnprocessable()
			->assertJsonValidationErrors(['rating']);
	}

	public function test_deleting_a_material_cascades_its_rankings(): void {
		$owner = User::factory()->create();
		$rater = User::factory()->create();
		$material = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		app(MaterialUserRankingService::class)->set($material, $rater, 9);

		$material->delete();

		$this->assertDatabaseMissing('material_user_ranking', ['material_id' => $material->id]);
	}

	public function test_final_user_deletion_removes_rankings_and_recalculates_remaining_default(): void {
		$owner = User::factory()->create();
		$deletedRater = User::factory()->create();
		$remainingRater = User::factory()->create();
		$material = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id, 'rating' => 1]);
		$service = app(MaterialUserRankingService::class);
		$service->set($material, $deletedRater, 4);
		$service->set($material, $remainingRater, 18);

		$deletedRater->delete();

		$this->assertDatabaseMissing('material_user_ranking', ['material_id' => $material->id, 'user_id' => $deletedRater->id]);
		$this->assertSame(18, $material->fresh()->rating);
	}

	public function test_search_uses_the_current_users_personal_ranking_before_the_default(): void {
		$user = User::factory()->create();
		$personalFavorite = Material::factory()->create(['created_by' => $user->id, 'modified_by' => $user->id, 'rating' => 1]);
		$defaultFavorite = Material::factory()->create(['created_by' => $user->id, 'modified_by' => $user->id, 'rating' => 19]);

		$this->actingAs($user)->postJson(route('pool.searchbar.get'), ['q' => []])
			->assertOk()
			->assertJsonPath('data.0.id', $defaultFavorite->id)
			->assertJsonPath('data.0.user_rating', null);

		app(MaterialUserRankingService::class)->set($personalFavorite, $user, 20);

		$this->actingAs($user)->postJson(route('pool.searchbar.get'), ['q' => []])
			->assertOk()
			->assertJsonPath('data.0.id', $personalFavorite->id)
			->assertJsonPath('data.0.user_rating', 20);
	}
}
