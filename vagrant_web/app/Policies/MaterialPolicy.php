<?php

namespace App\Policies;

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
	 * @param  \App\Models\User     $user
	 * @param  \App\Models\Material $material
	 * @return mixed
	 */
	public function view(User $user, Material $material) {
		return ($material->created_by == $user->id);
	}

	/**
	 * Determine whether the user can create materials.
	 *
	 * @param  \App\Models\User $user
	 * @return mixed
	 */
	public function create(User $user) {
		return TRUE;
	}

	/**
	 * Determine whether the user can update the material.
	 *
	 * @param  \App\Models\User     $user
	 * @param  \App\Models\Material $material
	 * @return mixed
	 */
	public function update(User $user, Material $material) {
		return ($material->created_by == $user->id);
	}

	/**
	 * Determine whether the user can delete the material.
	 *
	 * @param  \App\Models\User     $user
	 * @param  \App\Models\Material $material
	 * @return mixed
	 */
	public function delete(User $user, Material $material) {
		return ($material->created_by == $user->id);
	}


}
