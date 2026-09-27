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
	 * @param User $user
	 * @param ForeignMaterialId $foreignMaterialId
	 * @return mixed
	 */
	public function view(User $user, ForeignMaterialId $foreignMaterialId) {
		return $this->matchOrDeny($user, $foreignMaterialId);
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
	 * Determine whether the user can update the foreignMaterialId.
	 *
	 * @param User $user
	 * @param ForeignMaterialId $foreignMaterialId
	 * @return mixed
	 */
	public function update(User $user, ForeignMaterialId $foreignMaterialId) {
		return $this->matchOrDeny($user, $foreignMaterialId);
	}

	/**
	 * Determine whether the user can delete the foreignMaterialId.
	 *
	 * @param User $user
	 * @param ForeignMaterialId $foreignMaterialId
	 * @return mixed
	 */
	public function delete(User $user, ForeignMaterialId $foreignMaterialId) {
		return $this->matchOrDeny($user, $foreignMaterialId);
	}

	protected function matchOrDeny(User $user, ForeignMaterialId $foreignMaterialId) {
		if ($user->id === $foreignMaterialId->user_id) {
			return TRUE;
		} else {
			$this->deny('The signed in user does not match the foreignMaterialIds user.');
		}
	}
}
