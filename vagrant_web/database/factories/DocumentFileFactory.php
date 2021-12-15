<?php

namespace Database\Factories;

use App\Events\ResourceWasCreated;
use App\Models\DocumentFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFileFactory extends Factory {

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			'remote_path'  => 'http://some/file/path',
			'local_path'   => 'some/file/path',
			'content_hash' => 'just a fake hash',
			'notes'        => $this->faker->sentences(3, TRUE),
			'is_public'    => $this->faker->boolean(),
			'created_by'   => User::all()->random()->id,
		];
	}

	public function configure() {
		return $this->afterCreating(function (DocumentFile $resource) {
			event(new ResourceWasCreated($resource));
		});
	}

}