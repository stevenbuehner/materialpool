<?php

namespace App\Exceptions\Bundles;

use RuntimeException;

class BundleSourceValidationException extends RuntimeException {
	public function __construct(public readonly string $failureCode) {
		parent::__construct('Das Bundle kann nicht verarbeitet werden.');
	}
}
