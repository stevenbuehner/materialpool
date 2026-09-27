<?php

namespace Database\Factories;

use App\Models\Bundle;
use Illuminate\Database\Eloquent\Factories\Factory;

class BundleFactory extends Factory {
	protected $model = Bundle::class;

	public function definition(): array {
		return [
			'name' => $this->faker->unique()->words(3, true),
			'description' => $this->faker->sentence(),
			'uuid' => $this->faker->unique()->uuid,
			'container_root' => 'bundle-' . $this->faker->unique()->uuid,
		];
	}
}
