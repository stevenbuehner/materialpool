<?php

namespace Database\Factories;

use App\Models\AudioFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class AudioFileFactory extends Factory {

	protected $model = AudioFile::class;

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
			'is_public'    => $this->faker->boolean()
		];
	}

}