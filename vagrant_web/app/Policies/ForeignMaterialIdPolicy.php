<?php

namespace App\Policies;

use App\Models\ForeignMaterialId;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForeignMaterialIdPolicy {
	use HandlesAuthorization;

	/**
	 * Determine whether the user can view the foreignMaterialId.
	 *
	 * @param  \App\Models\User              $user
	 * @param  \App\Models\ForeignMaterialId $foreignMaterialId
	 * @return mixed
	 */
	public function view(User $user, ForeignMaterialId $foreignMaterialId) {
		return $user->id === $foreignMaterialId->user_id;
	}

	/**
	 * Determine whether the user can create foreignMaterialIds.
	 *
	 * @param  \App\Models\User $user
	 * @return mixed
	 */
	public function create(User $user) {
		//
	}

	/**
	 * Determine whether the user can update the foreignMaterialId.
	 *
	 * @param  \App\Models\User              $user
	 * @param  \App\Models\ForeignMaterialId $foreignMaterialId
	 * @return mixed
	 */
	public function update(User $user, ForeignMaterialId $foreignMaterialId) {
		return $user->id === $foreignMaterialId->user_id;
	}

	/**
	 * Determine whether the user can delete the foreignMaterialId.
	 *
	 * @param  \App\Models\User              $user
	 * @param  \App\Models\ForeignMaterialId $foreignMaterialId
	 * @return mixed
	 */
	public function delete(User $user, ForeignMaterialId $foreignMaterialId) {
		return $user->id === $foreignMaterialId->user_id;
	}
}
