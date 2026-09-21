<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'context_search';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        $schema->ensureVectorExtensionExists();

        $schema->create('context_search_index_generations', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('provider');
            $table->string('model');
            $table->string('model_digest')->nullable();
            $table->unsignedSmallInteger('embedding_dimensions');
            $table->string('chunking_version');
            $table->string('status')->default('draft');
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        $schema->create('context_search_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('resource_id');
            $table->char('source_hash', 64);
            $table->string('source_type');
            $table->string('extraction_method');
            $table->string('language', 16)->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->timestamps();

            $table->unique(['resource_id', 'source_hash']);
            $table->index('resource_id');
        });

        $schema->create('context_search_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('context_search_documents')
                ->cascadeOnDelete();
            $table->unsignedInteger('page_number');
            $table->text('content');
            $table->tsvector('content_tsv')->nullable();
            $table->jsonb('locator');
            $table->timestamps();

            $table->unique(['document_id', 'page_number']);
        });

        $schema->create('context_search_chunks_embeddinggemma_768', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('index_generation_id')
                ->constrained('context_search_index_generations')
                ->cascadeOnDelete();
            $table->foreignId('page_id')
                ->constrained('context_search_pages')
                ->cascadeOnDelete();
            $table->unsignedInteger('chunk_ordinal');
            $table->unsignedInteger('start_character');
            $table->unsignedInteger('end_character');
            $table->text('content');
            $table->tsvector('content_tsv')->nullable();
            $table->vector('embedding', 768)->nullable();
            $table->timestamps();

            $table->unique(['index_generation_id', 'page_id', 'chunk_ordinal']);
        });

        DB::connection(self::CONNECTION)->statement(
            'create index context_search_pages_content_tsv_gin on context_search_pages using gin (content_tsv)'
        );
        DB::connection(self::CONNECTION)->statement(
            'create index context_search_chunks_embeddinggemma_768_content_tsv_gin on context_search_chunks_embeddinggemma_768 using gin (content_tsv)'
        );
        DB::connection(self::CONNECTION)->statement(
            'create index context_search_chunks_embeddinggemma_768_embedding_hnsw on context_search_chunks_embeddinggemma_768 using hnsw (embedding vector_cosine_ops) with (m = 16, ef_construction = 64)'
        );
    }

    public function down(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        $schema->dropIfExists('context_search_chunks_embeddinggemma_768');
        $schema->dropIfExists('context_search_pages');
        $schema->dropIfExists('context_search_documents');
        $schema->dropIfExists('context_search_index_generations');
    }
};
