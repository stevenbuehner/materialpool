<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResourcesTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('resources', function (Blueprint $table) {
			$table->increments('id');

			$table->integer('created_by')->unsigned();
			$table->string('remote_path')->nullable();
			$table->string('local_path')->nullable();
			$table->text('notes')->nullable();
			$table->text('options');
			$table->boolean('is_public');
			$table->string('content_hash')->nullable();
			$table->string('type');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('resources');
	}
}
