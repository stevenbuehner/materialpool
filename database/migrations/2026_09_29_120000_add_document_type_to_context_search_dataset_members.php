<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('context_search_evaluation_dataset_members', function (Blueprint $table): void {
            $table->string('document_type', 32)->nullable()->after('source_revision_hash');
        });
    }

    public function down(): void {
        Schema::table('context_search_evaluation_dataset_members', function (Blueprint $table): void {
            $table->dropColumn('document_type');
        });
    }
};
