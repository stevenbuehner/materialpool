<?php

use Illuminate\Database\Migrations\Migration;

class AddIndicesResourceAndMaterials extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::table('material_resource', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->index('resource_id', 'matres_res_id_ind');
			$table->index('material_id', 'matres_mat_id_ind');
		});

		Schema::table('bibleverse_material', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->index('bibleverse_id', 'bibmat_bib_id_ind');
			$table->index('material_id', 'bibmat_mat_id_ind');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		Schema::table('material_resource', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropIndex('matres_res_id_ind');
			$table->dropIndex('matres_mat_id_ind');
		});

		Schema::table('bibleverse_material', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropIndex('bibmat_bib_id_ind');
			$table->dropIndex('bibmat_mat_id_ind');
		});

	}
}
