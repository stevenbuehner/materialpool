<?php

use Illuminate\Database\Migrations\Migration;

class AddDefaultTags extends Migration {

	const LANGS = ['Deutsch', 'Englisch', 'Französisch'];

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		foreach (self::LANGS as $lang) {
			(new \App\Models\Language(['title' => $lang]))->save();
		}

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		// Todo: Nicht getestet bisher !
		foreach (self::LANGS as $lang) {
			$model = \App\Models\Language::where('title', '=', $lang);

			if (!$model->materials()->exists()) {
				$model->delete();
			}

		}

	}


}
