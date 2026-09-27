<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddingMaterialFlags extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('materials', function (Blueprint $table) {
			$table->unsignedInteger('flag')->nullable();
			$table->index('flag', 'material_flag');
		});


	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('materials', function (Blueprint $table) {
			$table->dropIndex('material_flag');

			$table->dropColumn('flag');
		});
	}
}
