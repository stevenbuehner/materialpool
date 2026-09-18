<?php

namespace Tests\Feature\Bundles;

use App\Enums\BundleImportOperation;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class BundleImportStatusEndpointTest extends TestCase {
	use RefreshDatabase;

	public function test_bundle_manager_can_read_its_import_run_status_but_not_another_bundles_run(): void {
		$user = User::factory()->create(['is_admin' => TRUE]);
		$bundle = Bundle::factory()->create();
		$otherBundle = Bundle::factory()->create();
		$run = BundleImportRun::query()->create([
			'bundle_id' => $bundle->id,
			'operation' => BundleImportOperation::Update,
			'target_version' => '2.0.0',
			'queue_name' => 'bundle_' . $bundle->id . '_queue',
		]);
		Passport::actingAs($user);

		$this->getJson(route('api.v1.bundles.runs.status', [$bundle, $run]))
			->assertOk()
			->assertJsonPath('id', $run->id)
			->assertJsonPath('status', 'pending')
			->assertJsonPath('progress.total', 0);
		$this->getJson(route('api.v1.bundles.runs.status', [$otherBundle, $run]))->assertNotFound();
	}
}
