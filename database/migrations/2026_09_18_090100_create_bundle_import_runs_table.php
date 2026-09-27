<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void {
		Schema::create('bundle_import_runs', function (Blueprint $table): void {
			$table->uuid('id')->primary();
			$table->unsignedInteger('bundle_id');
			$table->unsignedInteger('requested_by')->nullable();
			$table->string('operation', 20)->index();
			$table->string('status', 20)->index();
			$table->string('phase', 32);
			$table->unsignedTinyInteger('active_slot')->nullable();
			$table->string('target_version', 191)->nullable();
			$table->char('source_fingerprint', 64)->nullable();
			$table->string('queue_name', 191);
			$table->string('current_batch_id')->nullable()->index();
			$table->string('validation_batch_id')->nullable();
			$table->string('delete_materials_batch_id')->nullable();
			$table->string('delete_resources_batch_id')->nullable();
			$table->string('resources_batch_id')->nullable();
			$table->string('materials_batch_id')->nullable();
			$table->unsignedInteger('expected_jobs')->default(0);
			$table->unsignedInteger('processed_jobs')->default(0);
			$table->string('failure_code', 100)->nullable();
			$table->string('failure_message', 1000)->nullable();
			$table->timestamp('started_at')->nullable();
			$table->timestamp('finished_at')->nullable();
			$table->timestamps();

			$table->foreign('bundle_id')->references('id')->on('bundles')->restrictOnDelete();
			$table->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
			$table->unique(['bundle_id', 'active_slot'], 'bundle_import_runs_active_slot_unique');
			$table->index(['bundle_id', 'created_at'], 'bundle_import_runs_bundle_created_index');
		});
	}

	public function down(): void {
		Schema::dropIfExists('bundle_import_runs');
	}
};
