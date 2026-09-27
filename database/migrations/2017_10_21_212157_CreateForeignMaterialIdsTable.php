<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForeignMaterialIdsTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('material_foreign_ids', function (Blueprint $table) {
			$table->increments('id');

			$table->integer('material_id');
			$table->integer('user_id');
			$table->string('foreign_id');

			$table->timestamps();

			$table->unique(['foreign_id', 'user_id']);
			$table->index('user_id', 'material_foreign_ids_user_id');
			$table->index('foreign_id');
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('material_foreign_ids');
	}
}
