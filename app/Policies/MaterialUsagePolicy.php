<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaterialUsagePolicy {
	use HandlesAuthorization;

	public function before($user, $ability) {
		if ($user->isSuperAdmin()) {
			return TRUE;
		}
	}

	/**
	 * Determine whether the user can view the material.
	 *
	 * @param User $user
	 * @param Material $materialUsage
	 * @return mixed
	 */
	public function view(User $user, MaterialUsage $materialUsage) {
		return TRUE;
	}

	/**
	 * Determine whether the user can create materials.
	 *
	 * @param User $user
	 * @return mixed
	 */
	public function create(User $user) {
		return TRUE;
	}

	/**
	 * Determine whether the user can update the material.
	 *
	 * @param User $user
	 * @param MaterialUsage $materialUsage
	 * @return mixed
	 */
	public function update(User $user, MaterialUsage $materialUsage) {
		return $this->matchOrDenyCreator($user, $materialUsage);
	}

	protected function matchOrDenyCreator(User $user, MaterialUsage $materialUsage) {
		if ($materialUsage->created_by === $user->id || $user->id === $materialUsage->material->created_by) {
			return TRUE;
		} else {
			$this->deny('The requested action is only allowed for the creator of this material.');
		}
	}

	/**
	 * Determine whether the user can delete the material.
	 *
	 * @param User $user
	 * @param MaterialUsage $materialUsage
	 * @return mixed
	 */
	public function delete(User $user, MaterialUsage $materialUsage) {
		return $this->matchOrDenyCreator($user, $materialUsage);
	}


}
