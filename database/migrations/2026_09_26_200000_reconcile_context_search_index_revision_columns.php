<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Early Dev databases ran the page-pipeline migration before these columns
        // were included. Fresh installations already have them.
        if (! Schema::hasColumn('context_search_index_run_resources', 'index_revision')) {
            Schema::table('context_search_index_run_resources', function (Blueprint $table): void {
                $table->char('index_revision', 64)->nullable();
            });
        }

        if (! Schema::hasColumn('context_search_resource_publications', 'index_revision')) {
            Schema::table('context_search_resource_publications', function (Blueprint $table): void {
                $table->char('index_revision', 64)->nullable();
            });
        }
    }

    public function down(): void
    {
        // The original page-pipeline migration owns these columns on fresh
        // installations. Dropping them here would destroy valid publication data.
    }
};
