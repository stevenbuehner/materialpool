<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AdminGroupController extends Controller {
	public function index(): array {
		return ['data' => Role::query()->where('guard_name', 'web')->with('permissions:id,name')->orderBy('name')->get()->map(fn(Role $role) => $this->serialize($role))->all()];
	}

	public function store(Request $request): JsonResponse {
		$validated = $this->validateGroup($request);
		$role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
		$role->syncPermissions($validated['permissions'] ?? []);
		Log::notice('admin.group.created', ['actor_id' => $request->user()->id, 'group_id' => $role->id]);

		return response()->json(['group' => $this->serialize($role->load('permissions:id,name'))], 201);
	}

	public function update(Role $group, Request $request): array {
		abort_unless($group->guard_name === 'web', 404);
		$validated = $this->validateGroup($request, $group, true);
		abort_if(
			$group->name === SystemPermissions::DEFAULT_GROUP
			&& isset($validated['name'])
			&& $validated['name'] !== SystemPermissions::DEFAULT_GROUP,
			409,
			'Die Standardgruppe kann nicht umbenannt werden.'
		);
		if (isset($validated['name'])) {
			$group->name = $validated['name'];
			$group->save();
		}
		if (array_key_exists('permissions', $validated)) {
			$group->syncPermissions($validated['permissions']);
		}
		Log::notice('admin.group.updated', ['actor_id' => $request->user()->id, 'group_id' => $group->id]);

		return ['group' => $this->serialize($group->load('permissions:id,name'))];
	}

	public function destroy(Role $group, Request $request) {
		abort_unless($group->guard_name === 'web', 404);
		$userCount = User::role($group)->count();
		abort_if($userCount > 0, 409, 'Die Gruppe ist noch Benutzern zugeordnet.');
		abort_if($group->name === SystemPermissions::DEFAULT_GROUP, 409, 'Die Standardgruppe kann nicht gelöscht werden.');
		$groupId = $group->id;
		$group->delete();
		Log::notice('admin.group.deleted', ['actor_id' => $request->user()->id, 'group_id' => $groupId]);

		return response()->noContent();
	}

	private function validateGroup(Request $request, ?Role $role = null, bool $partial = false): array {
		return $request->validate([
			'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id)],
			'permissions' => ['sometimes', 'array'],
			'permissions.*' => ['string', Rule::in(SystemPermissions::all())],
		]);
	}

	private function serialize(Role $role): array {
		return [
			'id' => $role->id,
			'name' => $role->name,
			'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
			'users_count' => User::role($role)->count(),
		];
	}
}
