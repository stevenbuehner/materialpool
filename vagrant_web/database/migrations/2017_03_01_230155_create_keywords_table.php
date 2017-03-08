<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSet;

class CreateKeywordsTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('keywords', function (Blueprint $table) {
			$table->increments('id');

			$table->string('title');
			$table->string('lc_title');
			$table->string('type');
			NestedSet::columns($table);

			$table->unique(['lc_title', 'type']);
			$table->index('title');
			$table->index('lc_title');

			$table->timestamps();
		});


		Schema::create('keyword_material', function (Blueprint $table) {

			$table->integer('material_id');
			$table->integer('keyword_id');
			$table->primary(['material_id', 'keyword_id']);

			$table->smallInteger('rating')->nullable();

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('keywords');

		Schema::dropIfExists('keyword_material');
	}
}
