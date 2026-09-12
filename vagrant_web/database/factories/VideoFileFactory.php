<?php

namespace Database\Factories;

use App\Events\ResourceWasCreated;
use App\Models\User;
use App\Models\VideoFile;
use Database\Factories\Concerns\CreatesLocalFileFixture;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFileFactory extends Factory {
	use CreatesLocalFileFixture;

	protected $model = VideoFile::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			'remote_path'  => NULL,
			'local_path'   => $this->localFixturePath('mp4'),
			'content_hash' => 'just a fake hash',
			'notes'        => $this->faker->sentences(3, TRUE),
			'is_public'    => $this->faker->boolean(),
			'original_filename' => 'Testvideo.mp4',
			'created_by'   => User::all()->random()->id
		];
	}

	public function configure() {
		return $this->withLocalFileFixture('Video.mp4')
			->afterCreating(function (VideoFile $resource) {
				event(new ResourceWasCreated($resource));
			});
	}

}
