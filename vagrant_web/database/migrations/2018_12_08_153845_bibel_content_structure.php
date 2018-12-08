<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class BibelContentStructure extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('bibles', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->increments('id')->unsigned();
			$table->string('uuid');
			$table->string('title');
			$table->text('description')->nullable();
			$table->dateTime('version_date')->nullable();
			$table->string('creator')->nullable();
			$table->string('language')->nullable();
			$table->string('rights');
			$table->text('source');
			$table->integer('usage_priority')->default(100);

			$table->timestamps();

			$table->unique('uuid', 'bibles_uuid');
			$table->index('usage_priority', 'bibles_usage_priority');

		});


		Schema::create('bible_contents', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->integer('bible_id')->unsigned();
			$table->integer('verse')->unsigned();
			$table->text('text');

			$table->unique(['bible_id', 'verse'], 'bible_content_bv');
			$table->index('verse', 'bible_content_verse');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('bible_contents');
		Schema::drop('bibles');
	}
}
