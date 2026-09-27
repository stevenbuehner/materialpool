<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContextSearchOcrCalibrationPage extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    public const SPLIT_CALIBRATION = 'calibration';
    public const SPLIT_HOLDOUT = 'holdout';

    protected $fillable = [
        'run_id', 'resource_id', 'source_revision_hash', 'page_number', 'split', 'status', 'metrics',
        'ocr_text', 'reference_text', 'quality_label', 'review_note', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'resource_id' => 'integer', 'page_number' => 'integer', 'metrics' => 'array',
            'reviewed_by' => 'integer', 'reviewed_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ContextSearchOcrCalibrationRun::class, 'run_id');
    }
}
