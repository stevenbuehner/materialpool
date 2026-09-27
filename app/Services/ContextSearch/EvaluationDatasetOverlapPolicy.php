<?php

namespace App\Services\ContextSearch;

/** Shared purpose-level rules for reusing complete evaluation blocks. */
final class EvaluationDatasetOverlapPolicy {
	/** @var list<string> */
	private const OPERATIONAL_PURPOSES = ['load', 'capacity'];

	public static function allows(string $firstPurpose, string $secondPurpose): bool {
		if ($firstPurpose === $secondPurpose) {
			return FALSE;
		}

		if (in_array($firstPurpose, self::OPERATIONAL_PURPOSES, TRUE)
			|| in_array($secondPurpose, self::OPERATIONAL_PURPOSES, TRUE)) {
			return TRUE;
		}

		return ($firstPurpose === 'ocr' && in_array($secondPurpose, ['calibration', 'acceptance'], TRUE))
			|| ($secondPurpose === 'ocr' && in_array($firstPurpose, ['calibration', 'acceptance'], TRUE));
	}

	/** @return list<string> Purposes whose members may coexist with this purpose. */
	public static function allowedPurposes(string $purpose): array {
		return match ($purpose) {
			'calibration' => ['ocr', 'load', 'capacity'],
			'acceptance' => ['ocr', 'load', 'capacity'],
			'ocr' => ['calibration', 'acceptance', 'load', 'capacity'],
			'load' => ['calibration', 'acceptance', 'ocr', 'capacity'],
			'capacity' => ['calibration', 'acceptance', 'ocr', 'load'],
			default => [],
		};
	}
}
