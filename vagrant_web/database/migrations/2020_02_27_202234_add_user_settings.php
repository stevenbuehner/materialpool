<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserSettings extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->json('frontend_user_settings')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropColumn('frontend_user_settings');
		});
	}
}
