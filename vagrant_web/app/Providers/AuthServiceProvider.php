<?php

namespace App\Providers;

use App\Models\Material;
use App\Models\Resource;
use App\Policies\MaterialPolicy;
use App\Policies\ResourcePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider {
	/**
	 * The policy mappings for the application.
	 *
	 * @var array
	 */
	protected $policies = [
		Material::class => MaterialPolicy::class,
		Resource::class => ResourcePolicy::class,
	];

	/**
	 * Register any authentication / authorization services.
	 *
	 * @return void
	 */
	public function boot() {
		$this->registerPolicies();
	}
}
