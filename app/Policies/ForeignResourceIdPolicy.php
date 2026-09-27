<?php

namespace App\Policies;

use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForeignResourceIdPolicy {
	use HandlesAuthorization;

	/**
	 * Determine whether the user can view the foreignResourceId.
	 *
	 * @param User $user
	 * @param ForeignResourceId $foreignResourceId
	 * @return mixed
	 */
	public function view(User $user, ForeignResourceId $foreignResourceId) {
		return $this->matchOrDeny($user, $foreignResourceId);
	}

	/**
	 * Determine whether the user can create foreignMaterialIds.
	 *
	 * @param User $user
	 * @return mixed
	 */
	public function create(User $user) {
		return TRUE;
	}

	/**
	 * Determine whether the user can update the foreignResourceId.
	 *
	 * @param User $user
	 * @param ForeignResourceId $foreignResourceId
	 * @return mixed
	 */
	public function update(User $user, ForeignResourceId $foreignResourceId) {
		return $this->matchOrDeny($user, $foreignResourceId);
	}

	/**
	 * Determine whether the user can delete the foreignResourceId.
	 *
	 * @param User $user
	 * @param ForeignResourceId $foreignResourceId
	 * @return mixed
	 */
	public function delete(User $user, ForeignResourceId $foreignResourceId) {
		return $this->matchOrDeny($user, $foreignResourceId);
	}

	protected function matchOrDeny(User $user, ForeignResourceId $foreignResourceId) {
		if ($user->id === $foreignResourceId->user_id) {
			return TRUE;
		} else {
			$this->deny('The signed in user does not match the foreignResourceIds user.');
		}
	}
}
