<?php

namespace App\Policies;

use App\Models\ForeignResourceId;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForeignResourceIdPolicy {
	use HandlesAuthorization;

	/**
	 * Determine whether the user can view the foreignResourceId.
	 *
	 * @param  \App\Models\User              $user
	 * @param  \App\Models\ForeignResourceId $foreignResourceId
	 * @return mixed
	 */
	public function view(User $user, ForeignResourceId $foreignResourceId) {
		return $user->id === $foreignResourceId->user_id;
	}

	/**
	 * Determine whether the user can create foreignMaterialIds.
	 *
	 * @param  \App\Models\User $user
	 * @return mixed
	 */
	public function create(User $user) {
		return TRUE;
	}

	/**
	 * Determine whether the user can update the foreignResourceId.
	 *
	 * @param  \App\Models\User              $user
	 * @param  \App\Models\ForeignResourceId $foreignResourceId
	 * @return mixed
	 */
	public function update(User $user, ForeignResourceId $foreignResourceId) {
		return $user->id === $foreignResourceId->user_id;
	}

	/**
	 * Determine whether the user can delete the foreignResourceId.
	 *
	 * @param  \App\Models\User              $user
	 * @param  \App\Models\ForeignResourceId $foreignResourceId
	 * @return mixed
	 */
	public function delete(User $user, ForeignResourceId $foreignResourceId) {
		return $user->id === $foreignResourceId->user_id;
	}
}
