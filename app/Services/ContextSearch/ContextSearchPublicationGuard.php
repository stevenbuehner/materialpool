<?php

namespace App\Services\ContextSearch;

use App\Models\ContextSearchResourcePublication;
use App\Models\Resource;
use RuntimeException;

/** Semantic search must apply its normal resource/material policies in addition to this revision guard. */
final class ContextSearchPublicationGuard {
	public function publishedRevision(Resource $resource, string $collection, string $embeddingProfile): ?string {
		$publication = ContextSearchResourcePublication::query()
			->where('collection_name', $collection)
			->where('embedding_profile', $embeddingProfile)
			->where('resource_id', $resource->getKey())
			->where('status', ContextSearchResourcePublication::STATUS_PUBLISHED)
			->first();

		if ($publication === NULL || $publication->document_revision === NULL || $publication->index_revision === NULL) {
			return NULL;
		}

		$currentResource = (new Resource())->newQueryWithoutScopes()->find($resource->getKey());
		if ($currentResource === NULL) {
			return NULL;
		}

		try {
			$current = app(ContextSearchSourceSnapshot::class)->revision($currentResource);
		} catch (RuntimeException) {
			return NULL;
		}

		$snapshot = app(ContextSearchSourceSnapshot::class);

		return hash_equals($publication->document_revision, $current)
		&& hash_equals($publication->index_revision, $snapshot->indexRevision($current, $embeddingProfile))
			? $publication->index_revision
			: NULL;
	}
}
