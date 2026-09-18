<?php

namespace App\Enums;

enum BundleImportOperation: string {
	case Install = 'install';
	case Update = 'update';
	case Uninstall = 'uninstall';
}
