<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBibleCrossReferences extends Migration {

	public const TABLE_NAME = 'bibleverses_cross_ref';

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create(self::TABLE_NAME, function (Blueprint $table) {

			$table->increments('id');

			$table->integer('source')->unsigned()->nullable(FALSE);
			$table->integer('relevance')->unsigned()->nullable(FALSE)->default(0);
			$table->integer('target_from')->unsigned()->nullable(FALSE);
			$table->integer('target_to')->unsigned()->nullable(FALSE);

			$table->unique(['source', 'target_from', 'target_to']);
			$table->index('source');
			$table->index('relevance');
			$table->index(['source', 'relevance']);

		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		Schema::drop(self::TABLE_NAME);

	}
}
