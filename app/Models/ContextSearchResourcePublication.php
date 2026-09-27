<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ContextSearchResourcePublication extends Model {
	public const STATUS_PUBLISHED = 'published';
	public const STATUS_WITHDRAWN = 'withdrawn';

	protected $fillable = ['collection_name', 'embedding_profile', 'resource_id', 'document_revision', 'index_revision', 'status'];

	protected function casts(): array {
		return ['resource_id' => 'integer'];
	}
}
