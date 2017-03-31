<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BibleverseMaterialTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('bibleverse_material', function (Blueprint $table) {

			$table->integer('bibleverse_id')->unsigned();
			$table->integer('material_id')->unsigned();
			$table->smallInteger('relevance')->nullable()->unsigned();
			$table->integer('author_id')->unsigned()->nullable();

			$table->primary(['bibleverse_id', 'material_id']);

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('bibleverse_material');
	}
}
