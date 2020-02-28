<?php

namespace App\Providers;

use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\Material;
use App\Models\Resource;
use App\Models\User;
use App\Policies\ForeignMaterialIdPolicy;
use App\Policies\ForeignResourceIdPolicy;
use App\Policies\MaterialPolicy;
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
		Material::class          => MaterialPolicy::class,
		Resource::class          => ResourcePolicy::class,
		ForeignMaterialId::class => ForeignMaterialIdPolicy::class,
		ForeignResourceId::class => ForeignResourceIdPolicy::class,
		User::class              => UserPolicy::class
	];

	/**
	 * Register any authentication / authorization services.
	 *
	 * @return void
	 */
	public function boot() {
		$this->registerPolicies();

		Passport::routes();

		// Expire tokens after one day
		Passport::tokensExpireIn(Carbon::now()->addDays(5));
		Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));

		Passport::cookie('materialpool_token');
	}
}
