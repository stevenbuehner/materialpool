<?php

namespace Database\Factories;

use App\Models\Text;
use Illuminate\Database\Eloquent\Factories\Factory;

class TextFactory extends Factory {
	protected $model = Text::class;

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {

		$content = 'Person: ' . $this->faker->name . ';';
		$content .= 'Title: ' . $this->faker->title . ';';
		$content .= 'vom: ' . $this->faker->date() . ';';

		$content .= "\n" . $this->faker->sentences(5, TRUE);

		return [
			'remote_path'  => NULL,
			'local_path'   => NULL,
			'content_hash' => 'just a fake hash',
			'content'      => $content,
			'notes'        => $this->faker->text(),
			'is_public'    => $this->faker->boolean()
		];

	}

}