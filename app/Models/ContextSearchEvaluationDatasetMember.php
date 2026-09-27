<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContextSearchEvaluationDatasetMember extends Model
{
    public const TYPE_MATERIAL = 'material';
    public const TYPE_RESOURCE = 'resource';

    protected $fillable = ['dataset_id', 'member_type', 'member_id', 'source_revision_hash'];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(ContextSearchEvaluationDataset::class, 'dataset_id');
    }
}
