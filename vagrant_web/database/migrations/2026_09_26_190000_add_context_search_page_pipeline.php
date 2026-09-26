<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('context_search_index_run_resources', function (Blueprint $table): void {
            $table->char('source_revision', 64)->nullable();
            $table->char('index_revision', 64)->nullable();
            $table->char('extraction_profile', 64)->nullable();
            $table->char('chunking_profile', 64)->nullable();
            $table->unsignedInteger('page_count')->nullable();
        });

        Schema::create('context_search_index_run_pages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('run_resource_id');
            $table->unsignedInteger('page_number');
            $table->string('status', 24)->index();
            $table->string('artifact_path', 512)->nullable();
            $table->char('artifact_hash', 64)->nullable();
            $table->string('extraction_method', 20)->nullable();
            $table->json('skip_reasons')->nullable();
            $table->unsignedInteger('indexed_chunks')->default(0);
            $table->string('failure_code', 80)->nullable();
            $table->timestamps();

            $table->foreign('run_resource_id')->references('id')->on('context_search_index_run_resources')->cascadeOnDelete();
            $table->unique(['run_resource_id', 'page_number'], 'context_search_run_page_unique');
        });

        Schema::create('context_search_resource_publications', function (Blueprint $table): void {
            $table->id();
            $table->string('collection_name', 255);
            $table->string('embedding_profile', 32);
            $table->unsignedInteger('resource_id');
            $table->char('document_revision', 64)->nullable();
            $table->char('index_revision', 64)->nullable();
            $table->string('status', 20);
            $table->timestamps();

            $table->unique(['collection_name', 'embedding_profile', 'resource_id'], 'context_search_publication_resource_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('context_search_resource_publications');
        Schema::dropIfExists('context_search_index_run_pages');
        Schema::table('context_search_index_run_resources', function (Blueprint $table): void {
            $table->dropColumn(['source_revision', 'index_revision', 'extraction_profile', 'chunking_profile', 'page_count']);
        });
    }
};
