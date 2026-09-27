<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Early Dev databases ran the page-pipeline migration before this
        // metadata field was included. Fresh installations already have it.
        if (! Schema::hasColumn('context_search_index_run_pages', 'skip_reasons')) {
            Schema::table('context_search_index_run_pages', function (Blueprint $table): void {
                $table->json('skip_reasons')->nullable();
            });
        }
    }

    public function down(): void
    {
        // The original page-pipeline migration owns this column on fresh
        // installations. Never drop potentially populated quality metadata.
    }
};
