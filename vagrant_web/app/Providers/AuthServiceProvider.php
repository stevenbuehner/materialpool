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
use Carbon\Carbon;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
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
	public function boot() {

		$this->registerPolicies();

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
