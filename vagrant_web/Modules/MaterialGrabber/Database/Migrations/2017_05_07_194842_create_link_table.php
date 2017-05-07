<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLinkTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('grabber_links', function (Blueprint $table) {
			$table->increments('id');

			$table->unsignedInteger('parent_id')->nullable();
			$table->unsignedInteger('grabber_id');
			$table->integer('priority');
			$table->string('url');
			$table->string('file_path')->nullable();
			$table->boolean('is_index');
			$table->text('options');
			$table->smallInteger('status');
			$table->date('last_check');
			$table->string('md5_cache')->nullable();
			$table->unsignedInteger('material_id')->nullable();

			$table->index(['parent_id'], 'grabber_link_parent_id_index');
			$table->index(['grabber_id'], 'grabber_link_grabber_id_index');
			$table->index(['url'], 'grabber_link_url_index');
			$table->index(['is_index'], 'grabber_link_is_index_index');
			$table->index(['status'], 'grabber_link_status_index');

			$table->foreign('grabber_id')
				  ->references('id')->on('grabber_grabbers')
				  ->onDelete('cascade')
				  ->onUpdate('cascade');

			$table->foreign('parent_id')
				  ->references('id')->on('grabber_links')
				  ->onDelete('cascade')
				  ->onUpdate('cascade');

			$table->foreign('material_id')
				  ->references('id')->on('materials')
				  ->onDelete('set null')
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
		Schema::dropIfExists('link');
	}
}
