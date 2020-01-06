<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ResourceKeys extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('resources', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->index(['is_public', 'created_by'], 'resources_auth_find_ind');
			$table->index('remote_path', 'resources_remote_path_ind');
			$table->index('updated_at', 'resources_updated_at_ind'); // For finding last resources
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('resources', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropIndex('resources_auth_find_ind');
			$table->dropIndex('resources_remote_path_ind');
			$table->dropIndex('resources_updated_at_ind');
		});
	}
}
