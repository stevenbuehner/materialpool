<?php

namespace Database\Factories;

use App\Events\ResourceWasCreated;
use App\Models\File;
use App\Models\User;
use Database\Factories\Concerns\CreatesLocalFileFixture;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory {
	use CreatesLocalFileFixture;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			'remote_path'       => 'https://www.allmystery.de/static/upics/942586_handy.jpg',
			'local_path'        => $this->localFixturePath('jpg'),
			'content_hash'      => 'just a fake hash',
			'notes'             => $this->faker->sentences(3, TRUE),
			'is_public'         => $this->faker->boolean(),
			'original_filename' => 'Ich bin ein Dateiname.jpg',
			'created_by'        => User::all()->random()->id
		];
	}

	public function configure() {
		return $this->withLocalFileFixture('Bild.jpg')
			->afterCreating(function (File $resource) {
				event(new ResourceWasCreated($resource));
			});
	}

}
