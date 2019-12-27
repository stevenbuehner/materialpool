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
	 * @param User $user
	 * @param Resource $resource
	 * @return mixed
	 */
	public function view(User $user, Resource $resource) {
		if ($resource->is_public) {
			return TRUE;
		}

		return $this->matchOrDenyCreator($user, $resource);
	}

	/**
	 * Determine whether the user can create resources.
	 *
	 * @param User $user
	 * @return mixed
	 */
	public function create(User $user) {
		return TRUE;
	}

	/**
	 * Determine whether the user can update the resource.
	 *
	 * @param User $user
	 * @param Resource $resource
	 * @return mixed
	 */
	public function update(User $user, Resource $resource) {
		return $this->matchOrDenyCreator($user, $resource);
	}

	/**
	 * Determine whether the user can delete the resource.
	 *
	 * @param User $user
	 * @param Resource $resource
	 * @return mixed
	 */
	public function delete(User $user, Resource $resource) {
		return $this->matchOrDenyCreator($user, $resource);
	}

	protected function matchOrDenyCreator(User $user, Resource $resource) {
		if ($user->id === $resource->created_by) {
			return TRUE;
		} else {
			$this->deny('The requested action is only allowed for the creator of this resource.');
		}
	}
}
