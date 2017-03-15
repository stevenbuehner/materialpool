<?php

namespace App\Providers;

use App\Http\View\Viewhelper\ResourceHelper;
use Illuminate\Support\ServiceProvider;

class HtmlResourceProvider extends ServiceProvider {

	protected $defer = TRUE;

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot() {
		//
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->singleton('ResourceHelper', function () {
			return new ResourceHelper();
		});
	}
}
