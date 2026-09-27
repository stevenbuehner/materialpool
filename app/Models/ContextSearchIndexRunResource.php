<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContextSearchIndexRunResource extends Model {
	public const STATUS_RUNNING             = 'running';
	public const STATUS_COMPLETED           = 'completed';
	public const STATUS_SKIPPED_LOW_QUALITY = 'skipped_low_quality';
	public const STATUS_FAILED              = 'failed';
	public const STATUS_PENDING             = 'pending';

	protected $fillable = [
		'run_id',
		'resource_id',
		'status',
		'indexed_chunks',
		'indexed_pages',
		'skipped_pages',
		'skip_reasons',
		'failure_message',
		'source_revision',
		'index_revision',
		'extraction_profile',
		'chunking_profile',
		'page_count',
	];

	protected function casts(): array {
		return [
			'resource_id'    => 'integer',
			'indexed_chunks' => 'integer',
			'indexed_pages'  => 'integer',
			'skipped_pages'  => 'integer',
			'skip_reasons'   => 'array',
			'page_count'     => 'integer',
		];
	}
}
