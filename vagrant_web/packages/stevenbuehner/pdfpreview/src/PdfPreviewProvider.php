<?php

namespace StevenBuehner\PdfPreview;

use Illuminate\Support\ServiceProvider;
use StevenBuehner\PdfPreview\Controllers\ListingController;

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

		// Public stuff
		$this->publishes([
							 __DIR__ . '/../public_resources' => public_path('vendor/pdfpreview'),
						 ], 'public');

		$this->mergeConfigFrom(__DIR__ . '/Config/PdfPreview.php', 'pdfpreview');
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {
		include __DIR__ . '/routes.php';
		$this->app->make(ListingController::class);

		// LocalPdfProvider

	}
}
