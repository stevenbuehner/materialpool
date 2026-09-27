<?php

use Illuminate\Database\Migrations\Migration;

class AddMaterialPreviewImageField extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('materials', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->integer('icon_of_bundle')->unsinged()->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('materials', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropColumn('icon_of_bundle');
		});
	}
}
