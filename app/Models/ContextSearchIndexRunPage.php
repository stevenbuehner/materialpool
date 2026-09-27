<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ContextSearchIndexRunPage extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_EXTRACTING = 'extracting';
    public const STATUS_EXTRACTED = 'extracted';
    public const STATUS_INDEXING = 'indexing';
    public const STATUS_INDEXED = 'indexed';
    public const STATUS_SKIPPED = 'skipped_low_quality';
    public const STATUS_FAILED = 'failed';
    public const STATUS_STALE = 'stale';

    protected $fillable = [
        'run_resource_id', 'page_number', 'status', 'artifact_path', 'artifact_hash',
        'extraction_method', 'skip_reasons', 'indexed_chunks', 'failure_code',
    ];

    protected function casts(): array
    {
        return ['run_resource_id' => 'integer', 'page_number' => 'integer', 'indexed_chunks' => 'integer', 'skip_reasons' => 'array'];
    }
}
