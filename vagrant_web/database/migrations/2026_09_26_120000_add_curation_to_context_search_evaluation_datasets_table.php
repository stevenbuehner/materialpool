<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('context_search_evaluation_datasets', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('purpose');
            $table->unsignedInteger('version')->default(1)->after('status');
            $table->unsignedInteger('target_material_count')->nullable()->after('resource_count');
            $table->unsignedInteger('target_resource_count')->nullable()->after('target_material_count');
            $table->json('target_quotas')->nullable()->after('target_resource_count');
            $table->timestamp('ready_at')->nullable()->after('frozen_at');
        });

        Schema::create('context_search_evaluation_dataset_members', function (Blueprint $table): void {
            $table->id();
            $table->uuid('dataset_id');
            $table->string('member_type', 16);
            $table->unsignedBigInteger('member_id');
            $table->string('source_revision_hash', 64)->nullable();
            $table->timestamps();

            $table->foreign('dataset_id')->references('id')->on('context_search_evaluation_datasets')->cascadeOnDelete();
            $table->unique(['member_type', 'member_id'], 'context_search_dataset_members_unique_member');
            $table->index(['dataset_id', 'member_type'], 'context_search_dataset_members_dataset_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('context_search_evaluation_dataset_members');

        Schema::table('context_search_evaluation_datasets', function (Blueprint $table): void {
            $table->dropColumn(['title', 'version', 'target_material_count', 'target_resource_count', 'target_quotas', 'ready_at']);
        });
    }
};
