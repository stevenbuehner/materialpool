<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('context_search_evaluation_dataset_members', function (Blueprint $table): void {
            $table->dropUnique('context_search_dataset_members_unique_member');
            $table->unique(['dataset_id', 'member_type', 'member_id'], 'context_search_dataset_members_unique_per_dataset');
            $table->index(['member_type', 'member_id'], 'context_search_dataset_members_member_lookup');
        });
    }

    public function down(): void
    {
        $hasOverlaps = DB::table('context_search_evaluation_dataset_members')
            ->select('member_type', 'member_id')
            ->groupBy('member_type', 'member_id')
            ->havingRaw('COUNT(DISTINCT dataset_id) > 1')
            ->exists();

        if ($hasOverlaps) {
            throw new \RuntimeException('OCR-Mitgliedschaften überlappen. Vor dem Rollback müssen die Überschneidungen explizit bereinigt werden.');
        }

        Schema::table('context_search_evaluation_dataset_members', function (Blueprint $table): void {
            $table->dropUnique('context_search_dataset_members_unique_per_dataset');
            $table->dropIndex('context_search_dataset_members_member_lookup');
            $table->unique(['member_type', 'member_id'], 'context_search_dataset_members_unique_member');
        });
    }
};
