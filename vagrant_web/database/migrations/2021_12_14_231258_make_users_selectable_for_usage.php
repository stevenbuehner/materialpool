<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeUsersSelectableForUsage extends Migration {

	const TABLE_NAME = 'users';

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table(self::TABLE_NAME, function (Blueprint $table) {
			$table->boolean('use_for_mat_usage')->nullable(FALSE)->default(TRUE);
			$table->index('use_for_mat_usage', 'users_use_for_mat_usage_index');
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		Schema::table(self::TABLE_NAME, function (Blueprint $table) {
			$table->dropIndex('users_use_for_mat_usage_index');
			$table->dropColumn('use_for_mat_usage');
		});

	}
}
