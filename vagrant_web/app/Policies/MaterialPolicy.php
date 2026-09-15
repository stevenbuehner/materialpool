<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaterialPolicy {
	use HandlesAuthorization;

	public function before($user, $ability) {
		if (!$user->isActive()) {
			return FALSE;
		}
		if ($user->isSuperAdmin()) {
			return TRUE;
		}
	}

	/**
	 * Determine whether the user can view the material.
	 *
	 * @param User $user
	 * @param Material $material
	 * @return mixed
	 */
	public function view(User $user, Material $material) {
		if ($user->id === $material->created_by) {
			return TRUE;
		}

		if ($material->is_public && $user->can(SystemPermissions::MATERIALS_VIEW_PUBLIC)) {
			return TRUE;
		}

		if ($user->can(SystemPermissions::MATERIALS_VIEW_ALL)) {
			return TRUE;
		}

		return Response::denyAsNotFound();
	}

	/**
	 * Determine whether the user can create materials.
	 *
	 * @param User $user
	 * @return mixed
	 */
	public function create(User $user) {
		return $user->can(SystemPermissions::MATERIALS_CREATE);
	}

	/**
	 * Determine whether the user can update the material.
	 *
	 * @param User $user
	 * @param Material $material
	 * @return mixed
	 */
	public function update(User $user, Material $material) {
		return $user->can(SystemPermissions::MATERIALS_UPDATE_ALL)
			|| ($user->id === $material->created_by && $user->can(SystemPermissions::MATERIALS_UPDATE_OWN));
	}

	public function updateMetadata(User $user, Material $material) {
		return $user->can(SystemPermissions::MATERIALS_UPDATE_METADATA_ALL)
			|| ($user->id === $material->created_by && $user->can(SystemPermissions::MATERIALS_UPDATE_METADATA_OWN));
	}

	/**
	 * Determine whether the user can delete the material.
	 *
	 * @param User $user
	 * @param Material $material
	 * @return mixed
	 */
	public function delete(User $user, Material $material) {
		return $user->can(SystemPermissions::MATERIALS_DELETE_ALL)
			|| ($user->id === $material->created_by && $user->can(SystemPermissions::MATERIALS_DELETE_OWN));
	}


}
