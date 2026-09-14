<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Support\Authorization\SystemPermissions;

class AdminPermissionController extends Controller {
	public function index(): array {
		return ['data' => collect(SystemPermissions::grouped())->flatMap(
			fn(array $permissions, string $area) => collect($permissions)->map(fn(string $code) => ['code' => $code, 'area' => $area])
		)->values()->all()];
	}
}
