<?php

namespace Database\Factories;

use App\Events\ResourceWasCreated;
use App\Models\AudioFile;
use App\Models\User;
use Database\Factories\Concerns\CreatesLocalFileFixture;
use Illuminate\Database\Eloquent\Factories\Factory;

class AudioFileFactory extends Factory {
	use CreatesLocalFileFixture;

	protected $model = AudioFile::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			'remote_path'  => NULL,
			'local_path'   => $this->localFixturePath('wav'),
			'content_hash' => 'just a fake hash',
			'notes'        => $this->faker->sentences(3, TRUE),
			'is_public'    => $this->faker->boolean(),
			'original_filename' => 'Testaudio.wav',
			'created_by'   => User::all()->random()->id
		];
	}

	public function configure() {
		return $this->withLocalFileFixture('Audio.wav')
			->afterCreating(function (AudioFile $resource) {
				event(new ResourceWasCreated($resource));
			});
	}


}
