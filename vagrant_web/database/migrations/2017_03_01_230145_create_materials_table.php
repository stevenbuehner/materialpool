<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialsTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('materials', function (Blueprint $table) {
			$table->increments('id');

			$table->string('title', 255);
			$table->text('description');
			$table->text('limitation')->nullable();
			$table->smallInteger('rating')->nullable()->unsigned(); // Between 0-64
			$table->boolean('from_bot');
			$table->integer('author_id')->nullable()->unsigned();

			$table->integer('created_by');
			$table->integer('modified_by');


			$table->timestamps();
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('materials');
	}
}
