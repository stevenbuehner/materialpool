<?php

namespace App\Support\Authorization;

use App\Models\Bundle;
use InvalidArgumentException;

final class BundlePermissionName {
	private const PREFIX = 'bundles.view.';

	private function __construct() {
	}

	public static function for(Bundle $bundle): string {
		if (!is_string($bundle->uuid) || trim($bundle->uuid) === '') {
			throw new InvalidArgumentException('Für eine Bundle-Leseberechtigung wird eine Bundle-UUID benötigt.');
		}

		return self::PREFIX . $bundle->uuid;
	}

	public static function uuidFrom(string $permission): ?string {
		if (!self::isBundlePermission($permission)) {
			return NULL;
		}

		return substr($permission, strlen(self::PREFIX));
	}

	public static function isBundlePermission(string $permission): bool {
		return str_starts_with($permission, self::PREFIX)
			&& trim(substr($permission, strlen(self::PREFIX))) !== '';
	}
}
