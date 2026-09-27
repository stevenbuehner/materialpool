<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy {
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
	 * Determine whether the user can view the = user.
	 *
	 * @param User $thisUser
	 * @param User $user
	 * @return mixed
	 */
	public function view(User $thisUser, User $user) {
		return $thisUser->is($user);
	}

	/**
	 * Determine whether the user can create = users.
	 *
	 * @param User $thisUser
	 * @return mixed
	 */
	public function create(User $thisUser) {
		return $thisUser->isSuperAdmin();
	}

	/**
	 * Determine whether the user can update the = user.
	 *
	 * @param User $thisUser
	 * @param User $user
	 * @return mixed
	 */
	public function update(User $thisUser, User $user) {
		return $thisUser->is($user);
	}

	/**
	 * Determine whether the user can delete the = user.
	 *
	 * @param User $thisUser
	 * @param User $user
	 * @return mixed
	 */
	public function delete(User $thisUser, User $user) {
		return $thisUser->isSuperAdmin();
	}

}
