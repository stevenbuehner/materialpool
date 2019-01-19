<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class BiggerLocalPath extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('resources', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->string('remote_path', 255)->change();
			$table->string('local_path', 512)->change();
			$table->string('type', 32)->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('resources', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->string('type', 191)->change();
			$table->string('local_path', 191)->change();
			$table->string('remote_path', 191)->change();
		});
	}
}
