<?php

namespace Database\Factories;

use App\Events\MaterialWasCreated;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory {
	protected $model = Material::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		$creatorId = User::all()->random()->id;
		return [
			'title'       => $this->faker->text(255),
			'description' => $this->faker->sentences(2, TRUE),
			'from_bot'    => $this->faker->boolean(),
			'is_public'   => TRUE,
			'rating'      => rand(0, 20),
			'created_by'  => $creatorId,
			'modified_by' => $creatorId,
		];
	}

	public function publiclyVisible() {
		return $this->state(['is_public' => TRUE]);
	}

	public function privatelyVisible() {
		return $this->state(['is_public' => FALSE]);
	}

	public function configure() {
		return $this->afterCreating(function (Material $resource) {
			event(new MaterialWasCreated($resource));
		});
	}

}
