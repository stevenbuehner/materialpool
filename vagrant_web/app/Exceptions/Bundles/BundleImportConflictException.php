<?php

namespace App\Exceptions\Bundles;

use App\Models\BundleImportRun;
use RuntimeException;

class BundleImportConflictException extends RuntimeException {
	public function __construct(public readonly BundleImportRun $activeRun) {
		parent::__construct('Für dieses Bundle läuft bereits eine andere Operation.');
	}
}
