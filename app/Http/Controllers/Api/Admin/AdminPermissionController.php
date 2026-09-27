<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;

use App\Models\Bundle;
use App\Services\Bundles\BundlePermissionService;
use App\Support\Authorization\BundlePermissionName;
use App\Support\Authorization\SystemPermissions;
use Spatie\Permission\Models\Permission;

class AdminPermissionController extends Controller {
	public function index(): array {
		$staticPermissions = collect(SystemPermissions::grouped())->flatMap(
			fn(array $permissions, string $area) => collect($permissions)->map(fn(string $code) => ['code' => $code, 'area' => $area])
		);
		$bundlesByUuid = Bundle::query()->orderBy('name')->get()->keyBy('uuid');
		$bundlePermissions = Permission::query()
			->whereIn('name', app(BundlePermissionService::class)->assignablePermissionNames())
			->where('guard_name', 'web')
			->orderBy('name')
			->get()
			->map(function (Permission $permission) use ($bundlesByUuid): array {
				$bundle = $bundlesByUuid->get(BundlePermissionName::uuidFrom($permission->name));

				return [
					'code' => $permission->name,
					'area' => 'bundle-read',
					'bundle' => [
						'id' => $bundle->id,
						'uuid' => $bundle->uuid,
						'name' => $bundle->name,
						'installed_version' => $bundle->installed_version,
						'is_installed' => $bundle->is_installed,
					],
				];
			});

		return ['data' => $staticPermissions->concat($bundlePermissions)->values()->all()];
	}
}
