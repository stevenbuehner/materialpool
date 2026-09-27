<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TurnResourceOptionsToBlob extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('resources', function (Blueprint $table) {
			// Typ Text hat Limitierungen in der Größe, bei JSON ist das nicht der Fall
			$table->json('options')->charset(null)->nullable()->change();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('resources', function (Blueprint $table) {
			$table->text('options')->nullable(FALSE)->change();
		});
	}
}
