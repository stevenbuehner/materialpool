<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForeignInstancesTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('foreign_instances', function (Blueprint $table) {

			$table->increments('id');
			$table->integer('user_id')->unsigned();
			$table->string('name');
			$table->text('info')->nullable();
			$table->string('api_key');
			$table->timestamps();

			$table->unique('api_key');
			$table->index('user_id');
			$table->unique('name');

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('foreign_instances');
	}
}
