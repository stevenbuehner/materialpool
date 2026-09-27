<?php

namespace App\Services\Bundles;

use App\Models\Bundle;
use App\Models\User;
use App\Support\Authorization\BundlePermissionName;
use Spatie\Permission\Models\Permission;

class BundlePermissionService {
	/**
	 * Erstellt die Berechtigung bei der Bundle-Erkennung. Sie wird absichtlich
	 * nicht beim Deinstallieren entfernt, damit bestehende Zuweisungen bei einem
	 * späteren erneuten Import wirksam bleiben.
	 */
	public function ensureFor(Bundle $bundle): Permission {
		return Permission::findOrCreate(BundlePermissionName::for($bundle), 'web');
	}

	/** @return array<string> */
	public function assignablePermissionNames(): array {
		$bundlesByUuid = Bundle::query()->pluck('id', 'uuid');

		return Permission::query()
			->where('guard_name', 'web')
			->pluck('name')
			->filter(fn(string $name): bool => ($uuid = BundlePermissionName::uuidFrom($name)) !== NULL && $bundlesByUuid->has($uuid))
			->values()
			->all();
	}

	/** @param iterable<int> $bundleIds */
	public function canReadAnyBundle(User $user, iterable $bundleIds): bool {
		$readableBundleIds = array_flip($this->readableBundleIds($user));

		foreach ($bundleIds as $bundleId) {
			if (isset($readableBundleIds[$bundleId])) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/** @return array<int> */
	public function readableBundleIds(User $user): array {
		$uuids = $user->getAllPermissions()
			->pluck('name')
			->map(fn(string $permission): ?string => BundlePermissionName::uuidFrom($permission))
			->filter()
			->unique()
			->values();

		if ($uuids->isEmpty()) {
			return [];
		}

		return Bundle::query()->whereIn('uuid', $uuids)->pluck('id')->all();
	}
}
