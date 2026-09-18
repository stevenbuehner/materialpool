<?php

namespace App\Enums;

enum BundleImportPhase: string {
	case Pending = 'pending';
	case Validating = 'validating';
	case DeletingMaterials = 'deleting_materials';
	case DeletingResources = 'deleting_resources';
	case UpsertingResources = 'upserting_resources';
	case UpsertingMaterials = 'upserting_materials';
	case Finalizing = 'finalizing';
}
