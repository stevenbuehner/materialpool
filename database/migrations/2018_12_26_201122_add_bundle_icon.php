<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddBundleIcon extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('bundles', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->string('icon')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('bundles', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropColumn('icon');
		});
	}
}
