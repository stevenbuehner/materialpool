<?php

namespace Database\Factories;

use App\Events\ResourceWasCreated;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class FileFactory extends Factory {

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		$testStorage = Storage::disk(config('app.disks.testfiles'));
		$liveStorage = Storage::disk(config('app.disks.resources'));

		$src        = $testStorage->read('Bild.jpg');
		$targetPath = uniqid('testing/') . '.jpg';
		$liveStorage->write($targetPath, $src);

		return [
			'remote_path'       => 'https://www.allmystery.de/static/upics/942586_handy.jpg',
			'local_path'        => config('app.disks.resources') . '::' . $targetPath,
			'content_hash'      => 'just a fake hash',
			'notes'             => $this->faker->sentences(3, TRUE),
			'is_public'         => $this->faker->boolean(),
			'original_filename' => 'Ich bin ein Dateiname.jpg',
			'created_by'        => User::all()->random()->id
		];
	}

	public function configure() {
		return $this->afterCreating(function (File $resource) {
			event(new ResourceWasCreated($resource));
		});
	}

}