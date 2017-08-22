<?php

namespace StevenBuehner\PdfPreview;

use Illuminate\Support\ServiceProvider;
use StevenBuehner\PdfPreview\Controllers\TestController;

class PdfPreviewProvider extends ServiceProvider {
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot() {
		$this->loadViewsFrom(__DIR__ . '/Views', 'PdfPreview');

		// be publishable
		$this->publishes([__DIR__ . '/Views'                 => resource_path('views/vendor/PdfPreview'),
						  __DIR__ . '/Config/PdfPreview.php' => config_path('pdfpreview.php'),
						 ]);

		$this->mergeConfigFrom(__DIR__ . '/Config/PdfPreview.php', 'pdfpreview');
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {
		include __DIR__ . '/routes.php';
		$this->app->make(TestController::class);

		// LocalPdfProvider

	}
}
