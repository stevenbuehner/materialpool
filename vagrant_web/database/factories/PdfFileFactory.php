<?php

namespace Database\Factories;

use App\Models\PdfFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class PdfFileFactory extends Factory {
	protected $model = PdfFile::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		$testStorage = Storage::disk(config('app.disks.testfiles'));
		$liveStorage = Storage::disk(config('app.disks.resources'));

		$src        = $testStorage->read('PDF.pdf');
		$targetPath = uniqid('testing/') . '.pdf';
		$liveStorage->write($targetPath, $src);

		return [
			'remote_path'  => 'http://www.ubtech.eu/wp-content/uploads/2013/02/BuecherBLUB.pdf',
			'local_path'   => config('app.disks.resources') . '::' . $targetPath,
			'content_hash' => 'just a fake hash',
			'notes'        => $this->faker->sentences(3, TRUE),
			'is_public'    => $this->faker->boolean()
		];
	}

}