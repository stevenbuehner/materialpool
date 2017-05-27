<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrabberConfigTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('grabber_config_values', function (Blueprint $table) {
			$table->increments('id');

			$table->string('name');
			$table->text('value');
			$table->unsignedInteger('grabber_id');

			$table->index(['name'], 'grabber_config_values_name_index');
			$table->index(['grabber_id'], 'grabber_config_values_grabber_id_index');
			$table->unique(['grabber_id', 'name'], 'grabber_config_values_both_index');

			$table->foreign('grabber_id')
				  ->references('id')->on('grabber_grabbers')
				  ->onDelete('cascade')
				  ->onUpdate('cascade');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('grabber_config_values');
	}
}
