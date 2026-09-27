<?php

use Illuminate\Database\Migrations\Migration;

class UserIsAdminMigration extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->boolean('is_admin')->default(FALSE);
		});

	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropColumn('is_admin');
		});
	}
}
