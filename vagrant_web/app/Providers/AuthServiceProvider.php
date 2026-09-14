<?php

namespace App\Providers;

use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\Resource;
use App\Models\User;
use App\Policies\BundlePolicy;
use App\Policies\ForeignMaterialIdPolicy;
use App\Policies\ForeignResourceIdPolicy;
use App\Policies\MaterialPolicy;
use App\Policies\MaterialUsagePolicy;
use App\Policies\ResourcePolicy;
use App\Policies\UserPolicy;
use App\Support\Authorization\SystemPermissions;
use Carbon\Carbon;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider {

	/**
	 * The policy mappings for the application.
	 *
	 * @var array
	 */
	protected $policies = [
		Bundle::class            => BundlePolicy::class,
		ForeignMaterialId::class => ForeignMaterialIdPolicy::class,
		ForeignResourceId::class => ForeignResourceIdPolicy::class,
		Material::class          => MaterialPolicy::class,
		MaterialUsage::class     => MaterialUsagePolicy::class,
		Resource::class          => ResourcePolicy::class,
		User::class              => UserPolicy::class,
	];

	/**
	 * Register any authentication / authorization services.
	 *
	 * @return void
	 */
	public function boot(): void {
		Gate::before(function (User $user): ?bool {
			if (!$user->isActive()) {
				return false;
			}

			return $user->isSuperAdmin() ? true : null;
		});
		Gate::define('keywords.create-value', fn(User $user): bool =>
			$user->can(SystemPermissions::KEYWORDS_MANAGE)
			|| $user->can(SystemPermissions::MATERIALS_CREATE)
			|| $user->can(SystemPermissions::MATERIALS_UPDATE_METADATA_OWN)
			|| $user->can(SystemPermissions::MATERIALS_UPDATE_METADATA_ALL)
		);
		Gate::define('bibleverses.create-value', fn(User $user): bool =>
			$user->can(SystemPermissions::MATERIALS_CREATE)
			|| $user->can(SystemPermissions::MATERIALS_UPDATE_METADATA_OWN)
			|| $user->can(SystemPermissions::MATERIALS_UPDATE_METADATA_ALL)
		);

		// Die Material-Grabber-Integration benötigt weiterhin den optionalen
		// Password Grant. Alle übrigen Passport-13-Defaults bleiben unverändert.
		Passport::enablePasswordGrant();

		// Expire tokens after one day
		Passport::tokensExpireIn(Carbon::now()->addDays(5));
		Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));
		Passport::personalAccessTokensExpireIn(now()->addMonths(6));

		// Passport keys werden generiert mit php artisan passport:keys
		// und liegen standardmäßig in /storage/oauth-private.key und /storage/oauth-public.key

		// im Middleware-Web wird ein Cookie generiert, dass anstelle von oAuth fungiert. Das ist der Name:
		Passport::cookie('materialpool_token');

	}
}
