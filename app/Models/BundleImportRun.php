<?php

namespace App\Models;

use App\Enums\BundleImportOperation;
use App\Enums\BundleImportPhase;
use App\Enums\BundleImportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleImportRun extends Model {
	use HasFactory;
	use HasUuids;

	public    $incrementing = FALSE;
	protected $keyType      = 'string';
	protected $fillable = [
		'bundle_id',
		'requested_by',
		'operation',
		'status',
		'phase',
		'active_slot',
		'target_version',
		'source_fingerprint',
		'source_warnings',
		'result_summary',
		'queue_name',
		'current_batch_id',
		'validation_batch_id',
		'delete_materials_batch_id',
		'delete_resources_batch_id',
		'resources_batch_id',
		'materials_batch_id',
		'expected_jobs',
		'processed_jobs',
		'failure_code',
		'failure_message',
		'started_at',
		'finished_at',
	];

	protected $attributes = [
		'status'         => BundleImportStatus::Pending->value,
		'phase'          => BundleImportPhase::Pending->value,
		'active_slot'    => 1,
		'expected_jobs'  => 0,
		'processed_jobs' => 0,
	];

	protected $casts = [
		'operation'       => BundleImportOperation::class,
		'status'          => BundleImportStatus::class,
		'phase'           => BundleImportPhase::class,
		'active_slot'     => 'integer',
		'expected_jobs'   => 'integer',
		'processed_jobs'  => 'integer',
		'source_warnings' => 'array',
		'result_summary'  => 'array',
		'started_at'      => 'datetime',
		'finished_at'     => 'datetime',
	];

	public function bundle(): BelongsTo {
		return $this->belongsTo(Bundle::class);
	}

	public function requestedBy(): BelongsTo {
		return $this->belongsTo(User::class, 'requested_by');
	}
}
