<?php

namespace App\Providers;

use App\Http\View\Helpers\ResourcePreviewHelper;
use Illuminate\Support\ServiceProvider;

class HtmlHelperProvider extends ServiceProvider {

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
		$this->app->singleton('ResourcePreview', ResourcePreviewHelper::class);
	}

	public function provides() {
		return ['ResourcePreview'];
	}
}
