<?php

use Illuminate\Database\Migrations\Migration;

class AddBundleStructure extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::create('bundles', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->string('installed_version')->nullable();
			$table->string('name');
			$table->text('description')->nullable();
			$table->dateTime('last_update')->nullable();
			$table->string('author')->nullable();
			$table->string('uuid')->nullable();
			$table->string('container_root');
			$table->boolean('is_installed');
			$table->boolean('update_available');

			$table->timestamps();
		});

		Schema::table('material_foreign_ids', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->integer('bundle_id')->nullable();
			$table->index('bundle_id', 'mat_for_ids_bundle_id');
		});

		Schema::table('resource_foreign_ids', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->integer('bundle_id')->nullable();
			$table->index('bundle_id', 'res_for_ids_bundle_id');
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		Schema::table('resource_foreign_ids', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropIndex('res_for_ids_bundle_id');
			$table->dropColumn('bundle_id');
		});

		Schema::table('material_foreign_ids', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropIndex('mat_for_ids_bundle_id');
			$table->dropColumn('bundle_id');
		});


		Schema::drop('bundles');
	}
}
