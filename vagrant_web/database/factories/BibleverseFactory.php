<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BibleverseFactory extends Factory {

	/**
	 * Define the model's default state.
	 *
	 * @return array
	 */
	public function definition() {
		$minFromChapter = rand(1, 50);
		$minVerse       = rand(1, 18);

		return [
			'book_id'      => 1,
			'from_chapter' => $minFromChapter,
			'to_chapter'   => rand($minFromChapter, 50),
			'from_verse'   => $minVerse,
			'to_verse'     => rand($minVerse, 18)
		];
	}

}