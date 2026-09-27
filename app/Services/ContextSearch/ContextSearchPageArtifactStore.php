<?php

namespace App\Services\ContextSearch;

use App\Services\ContextSearch\Extraction\ExtractedPage;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;
use Throwable;

final class ContextSearchPageArtifactStore {
	private const MAX_BYTES = 2_000_000;

	/** @return array{path: string, hash: string} */
	public function write(string $runId, int $resourceId, string $revision, ExtractedPage $page): array {
		$path = $this->path($runId, $resourceId, $revision, $page->pageNumber);

		try {
			$bytes = json_encode([
				'page_number'       => $page->pageNumber,
				'text'              => $page->text,
				'method'            => $page->method,
				'quality'           => $page->quality,
				'extractor_version' => $page->extractorVersion,
				'accepted'          => $page->accepted,
				'quality_metrics'   => $page->qualityMetrics,
				'quality_reasons'   => $page->qualityReasons,
			], JSON_THROW_ON_ERROR);
		} catch (JsonException $exception) {
			throw new RuntimeException('Extracted page cannot be serialized.', previous: $exception);
		}

		if (strlen($bytes) > self::MAX_BYTES) {
			throw new ContextSearchPageBudgetException('Extracted page exceeds the artifact size limit.');
		}

		$disk      = Storage::disk('local');
		$temporary = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';

		try {
			if (!$disk->put($temporary, $bytes, 'private') || !$disk->move($temporary, $path)) {
				throw new RuntimeException('Extracted page could not be stored atomically.');
			}
		} finally {
			$disk->delete($temporary);
		}

		return ['path' => $path, 'hash' => hash('sha256', $bytes)];
	}

	public function path(string $runId, int $resourceId, string $revision, int $pageNumber): string {
		if (!preg_match('/\A[a-f0-9-]{36}\z/i', $runId)
			|| !preg_match('/\A[a-f0-9]{64}\z/i', $revision)
			|| $resourceId < 1 || $pageNumber < 1) {
			throw new RuntimeException('Invalid context-search artifact identity.');
		}

		return "context-search-ocr-artifacts/{$runId}/{$resourceId}/{$revision}/page-{$pageNumber}.json";
	}

	public function read(string $path, string $expectedHash, int $expectedPageNumber): ?ExtractedPage {
		if (!preg_match('/\Acontext-search-ocr-artifacts\/[a-f0-9-]{36}\/[1-9][0-9]*\/[a-f0-9]{64}\/page-[1-9][0-9]*\.json\z/i', $path)
			|| !preg_match('/\A[a-f0-9]{64}\z/i', $expectedHash)) {
			return NULL;
		}

		try {
			$disk = Storage::disk('local');
			if (!$disk->exists($path) || $disk->size($path) > self::MAX_BYTES) {
				return NULL;
			}

			$bytes = $disk->get($path);
		} catch (Throwable) {
			// Cleanup may remove a disposable artifact between existence and read.
			return NULL;
		}
		if (!is_string($bytes) || !hash_equals($expectedHash, hash('sha256', $bytes))) {
			return NULL;
		}

		try {
			$data = json_decode($bytes, TRUE, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			return NULL;
		}

		if (!is_array($data) || ($data['page_number'] ?? NULL) !== $expectedPageNumber
			|| !is_string($data['text'] ?? NULL) || !is_string($data['method'] ?? NULL)
			|| !is_numeric($data['quality'] ?? NULL) || !is_string($data['extractor_version'] ?? NULL)
			|| !is_bool($data['accepted'] ?? NULL) || !is_array($data['quality_metrics'] ?? NULL)
			|| !is_array($data['quality_reasons'] ?? NULL)) {
			return NULL;
		}

		return new ExtractedPage(
			$expectedPageNumber,
			$data['text'],
			$data['method'],
			(float)$data['quality'],
			$data['extractor_version'],
			$data['accepted'],
			$data['quality_metrics'],
			$data['quality_reasons'],
		);
	}
}
