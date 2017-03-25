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
			$table->text('custom_icon')->nullable();
			NestedSet::columns($table);

			$table->unique(['lc_title', 'type']);
			$table->index('title');
			$table->index('lc_title');

			$table->timestamps();
		});


		Schema::create('keyword_material', function (Blueprint $table) {

			$table->integer('material_id');
			$table->integer('keyword_id');
			$table->smallInteger('relevance')->nullable()->unsigned();

			$table->primary(['material_id', 'keyword_id']);

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
