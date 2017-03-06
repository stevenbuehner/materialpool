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

			$table->string('title');
			$table->text('description');
			$table->text('limitation')->nullable();
			$table->integer('rating')->nullable();
			$table->boolean('from_bot');

			$table->integer('created_by')->nullable();
			$table->integer('modified_by')->nullable();


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
