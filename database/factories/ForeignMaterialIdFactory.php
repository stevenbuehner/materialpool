<?php

namespace Database\Factories;

use App\Models\ForeignMaterialId;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForeignMaterialIdFactory extends Factory {
	protected $model = ForeignMaterialId::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			'foreign_id' => $this->faker->unique()->uuid
		];
	}

}