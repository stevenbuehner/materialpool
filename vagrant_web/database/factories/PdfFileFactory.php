<?php

namespace Database\Factories;

use App\Events\ResourceWasCreated;
use App\Models\PdfFile;
use App\Models\User;
use Database\Factories\Concerns\CreatesLocalFileFixture;
use Illuminate\Database\Eloquent\Factories\Factory;

class PdfFileFactory extends Factory {
	use CreatesLocalFileFixture;
	protected $model = PdfFile::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			'remote_path'  => 'http://www.ubtech.eu/wp-content/uploads/2013/02/BuecherBLUB.pdf',
			'local_path'   => $this->localFixturePath('pdf'),
			'content_hash' => 'just a fake hash',
			'notes'        => $this->faker->sentences(3, TRUE),
			'is_public'    => $this->faker->boolean(),
			'created_by' => User::all()->random()->id
		];
	}

	public function configure() {
		return $this->withLocalFileFixture('PDF.pdf')
			->afterCreating(function (PdfFile $resource) {
				event(new ResourceWasCreated($resource));
			});
	}

}
