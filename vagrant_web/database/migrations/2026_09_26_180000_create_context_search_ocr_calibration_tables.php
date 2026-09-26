<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('context_search_index_run_resources', function (Blueprint $table): void {
            $table->unsignedSmallInteger('indexed_pages')->default(0)->after('indexed_chunks');
            $table->unsignedSmallInteger('skipped_pages')->default(0)->after('indexed_pages');
            $table->json('skip_reasons')->nullable()->after('skipped_pages');
        });

        Schema::create('context_search_ocr_calibration_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('dataset_id')->index();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->string('title', 255)->nullable();
            $table->string('status', 20)->index();
            $table->unsignedInteger('random_seed');
            $table->unsignedSmallInteger('sample_limit');
            $table->unsignedInteger('total_pages')->default(0);
            $table->unsignedInteger('processed_pages')->default(0);
            $table->unsignedInteger('reviewed_pages')->default(0);
            $table->json('ocr_profile');
            $table->json('sweep_results')->nullable();
            $table->json('approved_profile')->nullable();
            $table->char('approved_profile_hash', 64)->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('dataset_id')->references('id')->on('context_search_evaluation_datasets')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('context_search_ocr_calibration_pages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('run_id');
            $table->unsignedInteger('resource_id')->index();
            $table->char('source_revision_hash', 64)->index();
            $table->unsignedSmallInteger('page_number');
            $table->string('split', 16)->index();
            $table->string('status', 20)->index();
            $table->json('metrics')->nullable();
            $table->longText('ocr_text')->nullable();
            $table->longText('reference_text')->nullable();
            $table->string('quality_label', 20)->nullable()->index();
            $table->text('review_note')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('run_id')->references('id')->on('context_search_ocr_calibration_runs')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['run_id', 'resource_id', 'source_revision_hash', 'page_number'], 'ocr_calibration_page_unique');
            $table->index(['run_id', 'split', 'status'], 'ocr_calibration_page_progress');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('context_search_ocr_calibration_pages');
        Schema::dropIfExists('context_search_ocr_calibration_runs');

        Schema::table('context_search_index_run_resources', function (Blueprint $table): void {
            $table->dropColumn(['indexed_pages', 'skipped_pages', 'skip_reasons']);
        });
    }
};
