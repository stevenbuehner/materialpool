<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrabberTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('grabber_grabbers', function (Blueprint $table) {
			$table->increments('id');

			$table->boolean('is_active')->default(TRUE);
			$table->text('description')->nullable();
			$table->string('author');
			$table->string('name');
			$table->dateTime('last_complete_run')->nullable();

			$table->index(['name'], 'grabber_grabber_name_index');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('grabber_grabbers');
	}
}
