<?php

namespace App\Services\ContextSearch;

use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
use RuntimeException;

final class ContextSearchSourceSnapshot {
	public function revision(Resource $resource): string {
		if ($resource instanceof Text) {
			return hash('sha256', (string)$resource->getContent());
		}

		if ($resource instanceof PdfFile) {
			$path = $resource->getAbsoluteLocalPath();
			if (is_string($path) && is_file($path)) {
				$hash = hash_file('sha256', $path);
				if (is_string($hash)) {
					return $hash;
				}
			}
		}

		throw new RuntimeException('Context-search source is missing or unsupported.');
	}

	public function indexRevision(string $sourceRevision, string $embeddingProfile): string {
		return hash('sha256', implode(':', [
			$sourceRevision,
			$embeddingProfile,
			$this->extractionProfile(),
			$this->chunkingProfile(),
		]));
	}

	public function extractionProfile(): string {
		$settings = (array)config('context_search.indexing');
		$keys     = [
			'pdf_native_text_minimum_characters', 'ocr_languages', 'ocr_render_dpi',
			'ocr_max_image_pixels', 'ocr_page_segmentation_mode', 'ocr_engine_version',
			'ocr_quality_profile', 'ocr_quality_minimum_mean_confidence',
			'ocr_quality_minimum_recognized_words', 'ocr_quality_minimum_alphanumeric_ratio',
			'ocr_quality_maximum_replacement_character_ratio',
		];

		return hash('sha256', json_encode(array_intersect_key($settings, array_flip($keys)), JSON_THROW_ON_ERROR));
	}

	public function chunkingProfile(): string {
		return hash('sha256', json_encode(config('context_search.chunking'), JSON_THROW_ON_ERROR));
	}
}
