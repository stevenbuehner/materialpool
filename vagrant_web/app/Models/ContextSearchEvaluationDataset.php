<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ContextSearchEvaluationDataset extends Model
{
    use HasUuids;

    public const PURPOSES = ['calibration', 'acceptance', 'ocr', 'load', 'capacity'];
    public const STATUS_FROZEN = 'frozen';
    public const STATUS_EXPORTED = 'exported';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'purpose', 'status', 'includes_private', 'private_reason', 'manifest', 'manifest_hash',
        'material_count', 'resource_count', 'archive_path', 'archive_hash', 'frozen_at', 'exported_at',
    ];

    protected function casts(): array
    {
        return [
            'includes_private' => 'boolean',
            'manifest' => 'array',
            'material_count' => 'integer',
            'resource_count' => 'integer',
            'frozen_at' => 'datetime',
            'exported_at' => 'datetime',
        ];
    }
}
