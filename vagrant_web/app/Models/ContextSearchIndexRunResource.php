<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContextSearchIndexRunResource extends Model
{
    protected $fillable = [
        'run_id',
        'resource_id',
        'status',
        'indexed_chunks',
        'failure_message',
    ];

    protected function casts(): array
    {
        return [
            'resource_id' => 'integer',
            'indexed_chunks' => 'integer',
        ];
    }
}
