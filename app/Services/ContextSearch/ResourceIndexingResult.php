<?php

namespace App\Services\ContextSearch;

final readonly class ResourceIndexingResult {
	/** @param array<string, int> $skippedReasons */
	public function __construct(
		public int   $indexedChunks,
		public int   $totalPages,
		public int   $skippedPages,
		public array $skippedReasons,
	) {
	}
}
