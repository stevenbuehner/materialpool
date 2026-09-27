<?php

namespace App\Services\ContextSearch\Extraction;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Process;

final class TesseractOcrProcessor implements OcrProcessor {
	public function __construct(
		private readonly string $languages,
		private readonly int    $timeout,
		private readonly int    $renderDpi = 300,
		private readonly int    $pageSegmentationMode = 3,
		private readonly string $engineVersion = 'tesseract-5',
		private readonly int    $maxImagePixels = 12_000_000,
	) {
		if ($renderDpi < 1 || $maxImagePixels < 1) {
			throw new InvalidArgumentException('OCR render DPI and pixel budget must be positive.');
		}
	}

	public function extractPage(string $pdfPath, int $pageNumber): OcrResult {
		$temporaryDirectory = sys_get_temp_dir() . '/materialpool-ocr-' . bin2hex(random_bytes(8));

		if (!mkdir($temporaryDirectory, 0700) && !is_dir($temporaryDirectory)) {
			throw new RuntimeException('Temporary OCR directory could not be created.');
		}

		$imageBase  = $temporaryDirectory . '/page';
		$imagePath  = $imageBase . '.png';
		$outputBase = $temporaryDirectory . '/ocr';

		try {
			$effectiveDpi = $this->effectiveRenderDpi($pdfPath, $pageNumber);
			$this->run(['pdftoppm', '-f', (string)$pageNumber, '-l', (string)$pageNumber, '-r', (string)$effectiveDpi, '-png', '-singlefile', $pdfPath, $imageBase]);
			$this->run(['tesseract', $imagePath, $outputBase, '-l', $this->languages, '--dpi', (string)$effectiveDpi, '--psm', (string)$this->pageSegmentationMode, 'txt', 'tsv']);
			$text = file_get_contents($outputBase . '.txt');
			$tsv  = file_get_contents($outputBase . '.tsv');
			if ($text === FALSE || $tsv === FALSE) {
				throw new RuntimeException('OCR result files could not be read.');
			}

			$metrics               = $this->metrics($text, $tsv);
			$metrics['render_dpi'] = $effectiveDpi;

			return new OcrResult($text, $metrics['mean_confidence'], $this->engineVersion, $metrics);
		} finally {
			@unlink($imagePath);
			@unlink($outputBase . '.txt');
			@unlink($outputBase . '.tsv');
			@rmdir($temporaryDirectory);
		}
	}

	private function effectiveRenderDpi(string $pdfPath, int $pageNumber): int {
		$info    = $this->run(['pdfinfo', '-f', (string)$pageNumber, '-l', (string)$pageNumber, $pdfPath]);
		$pattern = '/^Page\s+' . preg_quote((string)$pageNumber, '/') . '\s+size:\s*([0-9]+(?:\.[0-9]+)?)\s+x\s+([0-9]+(?:\.[0-9]+)?)\s+pts\b/im';
		if (!preg_match($pattern, $info, $matches)
			&& !preg_match('/^Page size:\s*([0-9]+(?:\.[0-9]+)?)\s+x\s+([0-9]+(?:\.[0-9]+)?)\s+pts\b/im', $info, $matches)) {
			throw new RuntimeException('PDF page dimensions could not be read for OCR.');
		}

		$pageArea = (float)$matches[1] * (float)$matches[2];
		if ($pageArea <= 0) {
			throw new RuntimeException('PDF page dimensions are invalid for OCR.');
		}

		$maximumDpi   = (int)floor(sqrt($this->maxImagePixels * 72 * 72 / $pageArea));
		$effectiveDpi = min($this->renderDpi, $maximumDpi);
		while ($effectiveDpi > 1
			&& ceil((float)$matches[1] * $effectiveDpi / 72) * ceil((float)$matches[2] * $effectiveDpi / 72) > $this->maxImagePixels) {
			$effectiveDpi--;
		}
		if ($effectiveDpi < 1
			|| ceil((float)$matches[1] * $effectiveDpi / 72) * ceil((float)$matches[2] * $effectiveDpi / 72) > $this->maxImagePixels) {
			throw new RuntimeException('PDF page exceeds the OCR pixel budget.');
		}

		return $effectiveDpi;
	}

	/** @param array<int, string> $command */
	private function run(array $command): string {
		$process = new Process($command);
		if ($command[0] === 'tesseract') {
			$process->setEnv(['OMP_THREAD_LIMIT' => '1']);
		}
		$process->setTimeout($this->timeout);
		$process->run();

		if (!$process->isSuccessful()) {
			throw new RuntimeException('OCR command failed without exposing document contents.');
		}

		return $process->getOutput();
	}

	/** @return array{mean_confidence: float, median_confidence: float, low_confidence_word_ratio: float, recognized_word_count: int, alphanumeric_ratio: float, replacement_character_ratio: float, confidence_histogram: array<int, int>} */
	private function metrics(string $text, string $tsv): array {
		$confidences = [];
		$histogram   = array_fill(0, 101, 0);

		foreach (array_slice(preg_split('/\R/', $tsv) ?: [], 1) as $line) {
			$columns    = explode("\t", $line);
			$confidence = $columns[10] ?? NULL;
			$word       = trim($columns[11] ?? '');

			if ($word !== '' && is_numeric($confidence) && (float)$confidence >= 0) {
				$value         = (float)$confidence;
				$confidences[] = $value;
				$histogram[min(100, max(0, (int)floor($value)))]++;
			}
		}

		sort($confidences);
		$wordCount         = count($confidences);
		$characterCount    = mb_strlen(preg_replace('/\s+/u', '', $text) ?? '');
		$alphanumericCount = preg_match_all('/[\pL\pN]/u', $text) ?: 0;
		$replacementCount  = substr_count($text, "\u{FFFD}");

		$below50 = array_sum(array_slice($histogram, 0, 50));

		return [
			'mean_confidence'             => $wordCount === 0 ? 0.0 : array_sum($confidences) / $wordCount / 100,
			'median_confidence'           => $wordCount === 0 ? 0.0 : $this->median($confidences) / 100,
			'low_confidence_word_ratio'   => $wordCount === 0 ? 1.0 : $below50 / $wordCount,
			'recognized_word_count'       => $wordCount,
			'alphanumeric_ratio'          => $characterCount === 0 ? 0.0 : $alphanumericCount / $characterCount,
			'replacement_character_ratio' => $characterCount === 0 ? 0.0 : $replacementCount / $characterCount,
			'confidence_histogram'        => $histogram,
		];
	}

	/** @param array<int, float> $values */
	private function median(array $values): float {
		$middle = intdiv(count($values), 2);

		return count($values) % 2 === 0
			? ($values[$middle - 1] + $values[$middle]) / 2
			: $values[$middle];
	}
}
