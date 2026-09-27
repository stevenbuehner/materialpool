<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContextSearchOcrCalibrationRun extends Model
{
    use HasUuids;

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_EVALUATED = 'evaluated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_FAILED = 'failed';

    public const QUALITY_LABELS = ['usable', 'unusable', 'uncertain', 'handwriting', 'blank'];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'dataset_id', 'created_by', 'title', 'status', 'random_seed', 'sample_limit', 'total_pages',
        'processed_pages', 'reviewed_pages', 'ocr_profile', 'sweep_results', 'approved_profile',
        'approved_profile_hash', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'random_seed' => 'integer', 'sample_limit' => 'integer', 'total_pages' => 'integer',
            'processed_pages' => 'integer', 'reviewed_pages' => 'integer', 'ocr_profile' => 'array',
            'sweep_results' => 'array', 'approved_profile' => 'array', 'approved_at' => 'datetime',
        ];
    }

    public function pages(): HasMany
    {
        return $this->hasMany(ContextSearchOcrCalibrationPage::class, 'run_id');
    }
}
