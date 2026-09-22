<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ContextSearchIndexRun extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'status',
        'collection_name',
        'embedding_profile',
        'cursor_resource_id',
        'total_resources',
        'processed_resources',
        'failed_resources',
        'failure_message',
        'started_at',
        'finished_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'total_resources' => 0,
        'processed_resources' => 0,
        'failed_resources' => 0,
    ];

    protected function casts(): array
    {
        return [
            'cursor_resource_id' => 'integer',
            'total_resources' => 'integer',
            'processed_resources' => 'integer',
            'failed_resources' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
