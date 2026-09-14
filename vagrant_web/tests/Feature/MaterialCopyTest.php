<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class MaterialCopyTest extends TestCase {

	use RefreshDatabase;

	public function testCopyPreservesTheMaterialDate(): void {
		Event::fake();

		$user = User::factory()->create();
		$materialDate = Carbon::create(2018, 4, 12, 14, 30, 0);
		$material = Material::factory()->create([
			'created_by' => $user->id,
			'modified_by' => $user->id,
			'created_at' => $materialDate,
			'updated_at' => $materialDate,
		]);

		Passport::actingAs($user, []);

		$response = $this->getJson(route('api.v1.materials.copy', ['material' => $material]));

		$response->assertOk();
		$copy = Material::findOrFail($response->json('id'));

		$this->assertNotSame($material->id, $copy->id);
		$this->assertTrue($material->created_at->equalTo($copy->created_at));
	}
}
