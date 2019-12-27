<?php

namespace App\Policies;

use App\Models\ForeignResourceId;
use App\Models\Material;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaterialPolicy {
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
	 * @param Material $material
	 * @return mixed
	 */
	public function view(User $user, Material $material) {
		return $this->matchOrDenyCreator($user, $material);
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
	 * @param Material $material
	 * @return mixed
	 */
	public function update(User $user, Material $material) {
		return $this->matchOrDenyCreator($user, $material);
	}

	/**
	 * Determine whether the user can delete the material.
	 *
	 * @param User $user
	 * @param Material $material
	 * @return mixed
	 */
	public function delete(User $user, Material $material) {
		return $this->matchOrDenyCreator($user, $material);
	}

	protected function matchOrDenyCreator(User $user, Material $material) {
		if ($user->id === $material->created_by) {
			return TRUE;
		} else {
			$this->deny('The requested action is only allowed for the creator of this material.');
		}
	}


}
