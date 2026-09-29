<?php

namespace App\Support;

use RuntimeException;

class EnvironmentFile {
	public function assertWritable(): void {
		$path = realpath(base_path('.env'));
		if ($path === FALSE || !is_file($path) || !is_writable($path) || !function_exists('posix_geteuid') || posix_geteuid() !== 0) {
			throw new RuntimeException('Die Produktions-.env muss als root beschreibbar sein.');
		}
	}

	public function write(array $values): void {
		$this->assertWritable();
		$path = realpath(base_path('.env'));
		if ($path === FALSE) {
			throw new RuntimeException('Die Produktions-.env konnte nicht aufgelöst werden.');
		}

		$lock = fopen($path . '.lock', 'c');
		if ($lock === FALSE || !flock($lock, LOCK_EX)) {
			throw new RuntimeException('Die Produktions-.env kann nicht gesperrt werden.');
		}

		try {
			$content = file_get_contents($path);
			if ($content === FALSE) {
				throw new RuntimeException('Die Produktions-.env kann nicht gelesen werden.');
			}

			$lines = preg_split('/\r?\n/', rtrim($content, "\r\n")) ?: [];
			foreach ($values as $key => $value) {
				$replacement = $key . '=' . $this->formatValue((string)$value);
				$found = FALSE;
				foreach ($lines as &$line) {
					if (preg_match('/^' . preg_quote($key, '/') . '=/', $line) === 1) {
						$line = $replacement;
						$found = TRUE;
					}
				}
				unset($line);
				if (!$found) {
					$lines[] = $replacement;
				}
			}

			$temp = tempnam(dirname($path), '.env-update-');
			if ($temp === FALSE) {
				throw new RuntimeException('Temporäre .env-Datei kann nicht erstellt werden.');
			}
			try {
				if (file_put_contents($temp, implode("\n", $lines) . "\n", LOCK_EX) === FALSE
					|| !chmod($temp, fileperms($path) & 0777)
					|| !chown($temp, fileowner($path))
					|| !chgrp($temp, filegroup($path))
					|| !rename($temp, $path)) {
					throw new RuntimeException('Produktions-.env konnte nicht sicher aktualisiert werden.');
				}
			} finally {
				if (is_file($temp)) {
					unlink($temp);
				}
			}
		} finally {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}

	private function formatValue(string $value): string {
		if (preg_match('/^[A-Za-z0-9._~!@%+=:,\/-]*$/', $value) === 1) {
			return $value;
		}

		return '"' . str_replace(
			['\\', '"', '$', "\r", "\n"],
			['\\\\', '\\"', '\\$', '\\r', '\\n'],
			$value
		) . '"';
	}
}
