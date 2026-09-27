<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ForeignResourceIdFactory extends Factory {

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