<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaterialUsageEntries extends Migration {

	const TABLE_NAME = 'material_usages';

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		Schema::create(self::TABLE_NAME, function (Blueprint $table) {

			$table->increments('id');

			$table->unsignedInteger('material_id')->nullable(FALSE);
			// $table->unsignedInteger('order')->nullable(FALSE);
			$table->unsignedInteger('used_by_id')->nullable();
			$table->dateTime('datetime')->nullable(FALSE);
			$table->string('place')->default('')->nullable(FALSE);
			$table->string('reason')->default('')->nullable(FALSE);

			$table->unsignedInteger('created_by')->unsigned();
			$table->unsignedInteger('updated_by')->unsigned();

			$table->timestamps();

			// Indices
			$table->index(['material_id'], self::TABLE_NAME . '_index_material_id');
			// $table->unique(['material_id', 'order'], self::TABLE_NAME . '_index_mat_order');

			// Foreign IDs
			$table->foreign('material_id')->references('id')->on('materials')->onUpdate('cascade')->onDelete('cascade');
			$table->foreign('used_by_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
			$table->foreign('created_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');
			$table->foreign('updated_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('restrict');

		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		Schema::dropIfExists(self::TABLE_NAME);

	}
}
