<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ResourcePolicy {
	use HandlesAuthorization;

	public function before(User $user, $ability) {
		if (!$user->isActive()) {
			return FALSE;
		}
		if ($user->isSuperAdmin()) {
			return TRUE;
		}
	}

	/**
	 * Determine whether the user can view the resource.
	 *
	 * @param User $user
	 * @param Resource $resource
	 * @return mixed
	 */
	public function view(User $user, Resource $resource) {
		if ($resource->is_public) {
			return TRUE;
		}

		if ($user->id === $resource->created_by || $user->can(SystemPermissions::RESOURCES_VIEW_ALL)) {
			return TRUE;
		}

		return Response::denyAsNotFound();
	}

	/**
	 * Determine whether the user can create resources.
	 *
	 * @param User $user
	 * @return mixed
	 */
	public function create(User $user) {
		return $user->can(SystemPermissions::RESOURCES_CREATE);
	}

	/**
	 * Determine whether the user can update the resource.
	 *
	 * @param User $user
	 * @param Resource $resource
	 * @return mixed
	 */
	public function update(User $user, Resource $resource) {
		return $user->can(SystemPermissions::RESOURCES_UPDATE_ALL)
			|| ($user->id === $resource->created_by && $user->can(SystemPermissions::RESOURCES_UPDATE_OWN));
	}

	/**
	 * Determine whether the user can delete the resource.
	 *
	 * @param User $user
	 * @param Resource $resource
	 * @return mixed
	 */
	public function delete(User $user, Resource $resource) {
		return $user->can(SystemPermissions::RESOURCES_DELETE_ALL)
			|| ($user->id === $resource->created_by && $user->can(SystemPermissions::RESOURCES_DELETE_OWN));
	}
}
