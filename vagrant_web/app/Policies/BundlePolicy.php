<?php

namespace App\Policies;

use App\Models\Bundle;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BundlePolicy {
	use HandlesAuthorization;

	public function before($user, $ability) {
		if ($user->isSuperAdmin()) {
			return TRUE;
		}
	}

	/**
	 * Determine whether the user can view the bundle.
	 *
	 * @param User $user
	 * @param Bundle $bundle
	 * @return mixed
	 */
	public function view(User $user, Bundle $bundle) {
		return true;
	}

	/**
	 * Determine whether the user can create bundles.
	 *
	 * @param User $user
	 * @return mixed
	 */
	public function create(User $user) {
		return $user->isSuperAdmin();
	}

	/**
	 * Determine whether the user can update the bundle.
	 *
	 * @param User $user
	 * @param Bundle $bundle
	 * @return mixed
	 */
	public function update(User $user, Bundle $bundle) {
		return $user->isSuperAdmin();
	}

	/**
	 * Determine whether the user can delete the bundle.
	 *
	 * @param User $user
	 * @param Bundle $bundle
	 * @return mixed
	 */
	public function delete(User $user, Bundle $bundle) {
		return $user->isSuperAdmin();
	}

	protected function matchOrDenyCreator(User $user, Bundle $bundle) {
		return $user->isSuperAdmin();
	}


}
