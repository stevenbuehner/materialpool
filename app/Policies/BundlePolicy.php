<?php

namespace App\Policies;

use App\Models\Bundle;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class BundlePolicy {
	use HandlesAuthorization;

	public function before($user, $ability) {
		if (!$user->isActive()) {
			return FALSE;
		}
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
		return TRUE;
	}

	/**
	 * Determine whether the user can create bundles.
	 *
	 * @param User $user
	 * @return mixed
	 */
	public function create(User $user) {
		return $user->can(SystemPermissions::BUNDLES_MANAGE);
	}

	/**
	 * Determine whether the user can update the bundle.
	 *
	 * @param User $user
	 * @param Bundle $bundle
	 * @return mixed
	 */
	public function update(User $user, Bundle $bundle) {
		return $user->can(SystemPermissions::BUNDLES_MANAGE);
	}

	/**
	 * Determine whether the user can delete the bundle.
	 *
	 * @param User $user
	 * @param Bundle $bundle
	 * @return mixed
	 */
	public function delete(User $user, Bundle $bundle) {
		return $user->can(SystemPermissions::BUNDLES_MANAGE);
	}

	protected function matchOrDenyCreator(User $user, Bundle $bundle) {
		return $user->isSuperAdmin();
	}


}
