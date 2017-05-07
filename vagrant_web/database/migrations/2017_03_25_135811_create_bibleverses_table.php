<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBibleversesTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('bibleverses', function (Blueprint $table) {
			$table->increments('id');

			$table->integer('bible_id')->unsigned()->nullable();
			$table->integer('from')->unsigned();
			$table->integer('to')->unsigned();

			$table->unique(['bible_id', 'from', 'to']);
			$table->index(['from', 'to']);
			$table->index('from');
			$table->index('to');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('bibleverses');
	}
}
