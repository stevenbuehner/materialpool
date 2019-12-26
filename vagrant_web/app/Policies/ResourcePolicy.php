<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourcePolicy {
	use HandlesAuthorization;

	public function before(User $user, $ability) {
		if ($user->isSuperAdmin()) {
			return TRUE;
		}
	}

	/**
	 * Determine whether the user can view the resource.
	 *
	 * @param \App\Models\User $user
	 * @param \App\Models\Resource $resource
	 * @return mixed
	 */
	public function view(User $user, Resource $resource) {
		return ($resource->is_public || $resource->created_by == $user->id);
	}

	/**
	 * Determine whether the user can create resources.
	 *
	 * @param \App\Models\User $user
	 * @return mixed
	 */
	public function create(User $user) {
		return TRUE;
	}

	/**
	 * Determine whether the user can update the resource.
	 *
	 * @param \App\Models\User $user
	 * @param \App\Models\Resource $resource
	 * @return mixed
	 */
	public function update(User $user, Resource $resource) {
		return $resource->created_by == $user->id;
	}

	/**
	 * Determine whether the user can delete the resource.
	 *
	 * @param \App\Models\User $user
	 * @param \App\Models\Resource $resource
	 * @return mixed
	 */
	public function delete(User $user, Resource $resource) {
		return $resource->created_by == $user->id;
	}
}
