<?php

namespace App\Services;

use SplObjectStorage;
use Throwable;

class QueueJobDetailsPresenter {
	public function payload(string $raw): ?array {
		$payload = json_decode($raw, true);
		if (!is_array($payload)) return null;

		$command = $payload['data']['command'] ?? null;
		unset($payload['job'], $payload['data']['command']);

		if (is_string($command)) {
			// Never hydrate queued classes or invoke their serialization hooks while inspecting a job.
			try {
				$decoded = @unserialize($command, ['allowed_classes' => false]);
			} catch (Throwable) {
				$decoded = false;
			}
			if ($decoded !== false && is_object($decoded)) {
				$payload['data']['jobData'] = $this->readable($decoded, new SplObjectStorage());
			} else {
				$payload['data']['jobData'] = __('pool.queue-command-unavailable');
			}
		}

		return $this->readable($payload, new SplObjectStorage());
	}

	private function readable(mixed $value, SplObjectStorage $seen, int $depth = 0): mixed {
		if ($depth > 10) return null;
		if (is_object($value)) {
			if ($seen->contains($value)) return null;
			$seen->attach($value);
			$value = (array) $value;
		}
		if (is_array($value)) {
			$result = [];
			foreach ($value as $key => $item) {
				$key = is_string($key) ? preg_replace('/^.*\x00/', '', $key) : $key;
				if ($key === '__PHP_Incomplete_Class_Name') {
					$result['class'] = $item;
					continue;
				}
				if (is_string($key) && preg_match('/password|passphrase|secret|token|api.?key|authorization|credential|private.?key|cookie/i', $key)) {
					$result[$key] = __('pool.queue-redacted');
					continue;
				}
				$result[$key] = $this->readable($item, $seen, $depth + 1);
			}
			return $result;
		}
		if (is_string($value)) return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
		return $value;
	}
}
