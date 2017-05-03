<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class ResourceWithFileHash extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('resources', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->string('file_hash', 40)->default(NULL)->nullable();
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('resources', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropColumn('file_hash');
		});
	}
}
