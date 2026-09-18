<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void {
		Schema::create('material_user_ranking', function (Blueprint $table): void {
			$table->increments('id');
			$table->unsignedInteger('material_id');
			$table->unsignedInteger('user_id');
			$table->unsignedTinyInteger('rating');
			$table->timestamps();

			$table->unique(['material_id', 'user_id']);
			$table->index('user_id');
			$table->foreign('material_id')->references('id')->on('materials')->cascadeOnDelete();
			$table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
		});
	}

	public function down(): void {
		Schema::dropIfExists('material_user_ranking');
	}
};
