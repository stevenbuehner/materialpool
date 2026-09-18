<?php

namespace Tests\Feature\Bundles;

use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreflightBundleIdentifiersCommandTest extends TestCase {
	use RefreshDatabase;

	public function test_it_passes_for_distinct_bundle_identifiers(): void {
		Bundle::factory()->count(2)->create();

		$this->artisan('bundles:preflight-identifiers')
			->expectsOutputToContain('Preflight bestanden')
			->assertExitCode(0);
	}

	public function test_it_reports_uuid_and_foreign_id_conflicts_without_changing_data(): void {
		$owner = User::factory()->create();
		$otherOwner = User::factory()->create();
		$first = Bundle::factory()->create(['uuid' => 'same-bundle-uuid']);
		$second = Bundle::factory()->create(['uuid' => 'same-bundle-uuid']);
		$firstMaterial = Material::factory()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
		$secondMaterial = Material::factory()->create(['created_by' => $otherOwner->id, 'modified_by' => $otherOwner->id]);
		ForeignMaterialId::create(['material_id' => $firstMaterial->id, 'user_id' => $owner->id, 'foreign_id' => 'same-foreign-id', 'bundle_id' => $first->id]);
		ForeignMaterialId::create(['material_id' => $secondMaterial->id, 'user_id' => $otherOwner->id, 'foreign_id' => 'same-foreign-id', 'bundle_id' => $second->id]);

		$this->artisan('bundles:preflight-identifiers')
			->expectsOutputToContain('Doppelte Bundle-UUIDs')
			->expectsOutputToContain('Preflight nicht bestanden')
			->assertExitCode(1);
		$this->assertDatabaseCount('bundles', 2);
		$this->assertDatabaseCount('material_foreign_ids', 2);
	}
}
