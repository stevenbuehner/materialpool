<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bible_data_imports', function (Blueprint $table): void {
            $table->id();
            $table->string('dataset_id', 191)->unique();
            $table->string('kind', 32);
            $table->string('title');
            $table->string('language', 32)->nullable();
            $table->text('rights')->nullable();
            $table->text('source_url');
            $table->string('source_path')->nullable();
            $table->string('applied_hash', 64)->nullable();
            $table->string('observed_hash', 64)->nullable();
            $table->unsignedSmallInteger('normalization_version');
            $table->string('applied_revision', 64)->nullable();
            $table->string('applied_blob', 64)->nullable();
            $table->string('observed_revision', 64)->nullable();
            $table->string('observed_blob', 64)->nullable();
            $table->unsignedInteger('row_count');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_data_imports');
    }
};
