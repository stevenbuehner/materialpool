<?php

namespace Database\Factories;

use App\Events\ResourceWasCreated;
use App\Models\DocumentFile;
use App\Models\User;
use Database\Factories\Concerns\CreatesLocalFileFixture;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFileFactory extends Factory {
	use CreatesLocalFileFixture;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		return [
			'remote_path'  => NULL,
			'local_path'   => $this->localFixturePath('docx'),
			'content_hash' => 'just a fake hash',
			'notes'        => $this->faker->sentences(3, TRUE),
			'is_public'    => $this->faker->boolean(),
			'original_filename' => 'Testdokument.docx',
			'created_by'   => User::all()->random()->id,
		];
	}

	public function configure() {
		return $this->withLocalFileFixture('Document.docx')
			->afterCreating(function (DocumentFile $resource) {
				event(new ResourceWasCreated($resource));
			});
	}

}
