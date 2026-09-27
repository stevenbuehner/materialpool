<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('context_search_index_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('status', 20)->index();
            $table->string('collection_name', 255);
            $table->string('embedding_profile', 32)->index();
            $table->unsignedInteger('cursor_resource_id')->nullable()->index();
            $table->unsignedInteger('total_resources')->default(0);
            $table->unsignedInteger('processed_resources')->default(0);
            $table->unsignedInteger('failed_resources')->default(0);
            $table->string('failure_message', 1000)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('context_search_index_run_resources', function (Blueprint $table): void {
            $table->id();
            $table->uuid('run_id');
            $table->unsignedInteger('resource_id');
            $table->string('status', 20)->index();
            $table->unsignedInteger('indexed_chunks')->default(0);
            $table->string('failure_message', 1000)->nullable();
            $table->timestamps();

            $table->foreign('run_id')->references('id')->on('context_search_index_runs')->cascadeOnDelete();
            $table->unique(['run_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('context_search_index_run_resources');
        Schema::dropIfExists('context_search_index_runs');
    }
};
