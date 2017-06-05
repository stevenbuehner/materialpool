<?php

namespace Modules\MaterialGrabber\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Modules\MaterialGrabber\Console\Config;
use Modules\MaterialGrabber\Console\Run;
use Modules\MaterialGrabber\GrabberTemplates\Helper\FileHashHelper;
use Modules\MaterialGrabber\GrabberTemplates\Helper\Md5Helper;
use Modules\MaterialGrabber\GrabberTemplates\Helper\SessionAwareClientDownload;
use Modules\MaterialGrabber\GrabberTemplates\Helper\SessionAwareCurlDownload;
use Modules\MaterialGrabber\Services\GrabberService;
use Modules\MaterialGrabber\Services\GrabManager;
use Modules\MaterialGrabber\Services\LinkManager;

class MaterialGrabberServiceProvider extends ServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = FALSE;

	/**
	 * Boot the application events.
	 *
	 * @return void
	 */
	public function boot() {
		$this->registerTranslations();
		$this->registerConfig();
		// $this->registerViews();
		$this->registerListeners();
	}

	/**
	 * Register translations.
	 *
	 * @return void
	 */
	public function registerTranslations() {
		$langPath = base_path('resources/lang/modules/materialgrabber');

		if (is_dir($langPath)) {
			$this->loadTranslationsFrom($langPath, 'materialgrabber');
		} else {
			$this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'materialgrabber');
		}
	}

	/**
	 * Register config.
	 *
	 * @return void
	 */
	protected function registerConfig() {
		$this->publishes([
							 __DIR__ . '/../Config/config.php' => config_path('materialgrabber.php'),
						 ], 'config');
		$this->mergeConfigFrom(
			__DIR__ . '/../Config/config.php', 'materialgrabber'
		);
	}

	public function registerListeners() {
		// Event::listen('grabber.register');
	}

	/**
	 * Register views.
	 *
	 * @return void
	 */
	public function registerViews() {
		$viewPath = base_path('resources/views/modules/materialgrabber');

		$sourcePath = __DIR__ . '/../Resources/views';

		$this->publishes([
							 $sourcePath => $viewPath
						 ]);

		$this->loadViewsFrom(array_merge(array_map(function ($path) {
			return $path . '/modules/materialgrabber';
		}, \Config::get('view.paths')), [$sourcePath]), 'materialgrabber');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {

		if (!$this->app->runningInConsole()) {
			return;
		}

		$this->app->singleton(GrabberService::class);
		$this->app->singleton(GrabManager::class);
		$this->app->singleton('grabber.disk', function () {
			return Storage::disk('grabber');
		});

		// $this->app->singleton('grabber.grabberservice', GrabberService::class);

		$this->app->singleton('grabber.linkmanager', LinkManager::class);
		$this->app->singleton('grabber.curldownload', SessionAwareCurlDownload::class);
		$this->app->singleton('grabber.sessiondownload', SessionAwareClientDownload::class);
		$this->app->singleton(FileHashHelper::class, Md5Helper::class);

		$this->commands([
							Config::class,
							Run::class
						]);

		// $this->app->singleton('link.helper.download', DownloadHelper::class);
		// $this->app->singleton('link.helper.download2', Download2Helper::class);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return [];
	}
}
