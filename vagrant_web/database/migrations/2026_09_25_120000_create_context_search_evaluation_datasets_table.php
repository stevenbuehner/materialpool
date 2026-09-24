<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('context_search_evaluation_datasets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('purpose', 20)->index();
            $table->string('status', 20)->index();
            $table->boolean('includes_private')->default(false);
            $table->string('private_reason', 500)->nullable();
            $table->json('manifest');
            $table->char('manifest_hash', 64)->unique();
            $table->unsignedInteger('material_count')->default(0);
            $table->unsignedInteger('resource_count')->default(0);
            $table->string('archive_path', 512)->nullable();
            $table->char('archive_hash', 64)->nullable();
            $table->timestamp('frozen_at')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('context_search_evaluation_datasets');
    }
};
