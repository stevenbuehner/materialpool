<?php

use Illuminate\Database\Migrations\Migration;

class AddIndexesForResources extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('resources', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->index('content_hash', 'resources_content_hash_ind');
			$table->index('created_by', 'resources_created_by_ind');
			$table->index('type', 'resources_type_ind');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('resources', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropIndex('resources_content_hash_ind');
			$table->dropIndex('resources_created_by_ind');
			$table->dropIndex('resources_type_ind');
		});
	}
}
