<?php

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory {
	protected $model = Material::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			'title'       => $this->faker->text(255),
			'description' => $this->faker->sentences(2, TRUE),
			'from_bot'    => $this->faker->boolean(),
			'rating'      => rand(0, 20)
		];
	}

}