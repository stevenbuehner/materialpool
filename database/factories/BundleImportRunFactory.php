<?php

namespace Database\Factories;

use App\Enums\BundleImportOperation;
use App\Models\Bundle;
use App\Models\BundleImportRun;
use Illuminate\Database\Eloquent\Factories\Factory;

class BundleImportRunFactory extends Factory {
	protected $model = BundleImportRun::class;

	public function definition(): array {
		return [
			'bundle_id' => Bundle::factory(),
			'operation' => BundleImportOperation::Update,
			'queue_name' => 'bundle_factory_queue',
		];
	}
}
