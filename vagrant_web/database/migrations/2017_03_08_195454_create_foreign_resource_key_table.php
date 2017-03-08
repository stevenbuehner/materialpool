<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateForeignResourceKeyTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('foreign_resource_keys', function (Blueprint $table) {

			$table->integer('remote_id');
			$table->integer('resource_id');
			$table->integer('foreign_instance_id');

			$table->primary(['remote_id', 'resource_id', 'foreign_instance_id'], 'remote_instance_resource_id_primary');
			$table->index(['remote_id', 'foreign_instance_id']);
			$table->index('resource_id');
			$table->index('foreign_instance_id');

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('foreign_resource_keys');
	}
}
