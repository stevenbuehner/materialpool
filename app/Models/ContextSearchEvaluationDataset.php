<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContextSearchEvaluationDataset extends Model
{
    use HasUuids;

    public const PURPOSES = ['calibration', 'acceptance', 'ocr', 'load', 'capacity'];
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_FROZEN = 'frozen';
    public const STATUS_EXPORTED = 'exported';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'purpose', 'title', 'status', 'version', 'includes_private', 'private_reason', 'manifest', 'manifest_hash',
        'material_count', 'resource_count', 'target_material_count', 'target_resource_count', 'target_quotas',
        'archive_path', 'archive_hash', 'ready_at', 'frozen_at', 'exported_at',
    ];

    protected function casts(): array
    {
        return [
            'includes_private' => 'boolean',
            'manifest' => 'array',
            'material_count' => 'integer',
            'resource_count' => 'integer',
            'version' => 'integer',
            'target_material_count' => 'integer',
            'target_resource_count' => 'integer',
            'target_quotas' => 'array',
            'ready_at' => 'datetime',
            'frozen_at' => 'datetime',
            'exported_at' => 'datetime',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(ContextSearchEvaluationDatasetMember::class, 'dataset_id');
    }
}
