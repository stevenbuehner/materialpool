<?php

namespace App\Enums;

enum BundleImportStatus: string {
	case Pending = 'pending';
	case Running = 'running';
	case Succeeded = 'succeeded';
	case Failed = 'failed';
}
