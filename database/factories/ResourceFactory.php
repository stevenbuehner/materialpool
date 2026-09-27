<?php

namespace Database\Factories;

use App\Events\ResourceWasCreated;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResourceFactory extends Factory {
	protected $model = Resource::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			'remote_path'  => 'https://www.allmystery.de/static/upics/942586_handy.jpg',
			'local_path'   => 'some/file/path',
			'content_hash' => 'just a fake hash',
			'notes'        => $this->faker->sentences(3, TRUE),
			'is_public'    => $this->faker->boolean(),
			'created_by'   => User::all()->random()->id
		];
	}


	public function configure() {
		return $this->afterCreating(function (Resource $resource) {
			event(new ResourceWasCreated($resource));
		});
	}


}