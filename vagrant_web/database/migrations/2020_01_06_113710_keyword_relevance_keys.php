<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class KeywordRelevanceKeys extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('keyword_material', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->index(['relevance'], 'keyword_mat_relevance_ind');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('resources', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->dropIndex('relevance');
		});
	}
}
