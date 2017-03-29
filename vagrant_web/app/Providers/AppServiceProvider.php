<?php

namespace App\Providers;

use App\Services\ResourceRecognition\ResourceRecognitionService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

class AppServiceProvider extends ServiceProvider {
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot() {
		Schema::defaultStringLength(191);
	}

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register() {
		if ($this->app->environment() !== 'production') {
			$this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
		}

		if ($this->app->environment() == 'local') {
			// $this->app->register('Laracasts\Generators\GeneratorsServiceProvider'); // you're using Jeffrey way's generators, too, right?
			$this->app->register('Backpack\Generators\GeneratorsServiceProvider');
		}


		if ($this->app->environment() == 'local') {
			$this->app->register('Barryvdh\Debugbar\ServiceProvider');

			$this->app->alias('Barryvdh\Debugbar\Facade', 'Debugbar');
		}

		$this->app->singleton('BibleVerseService', function ($app) {
			return new BibleVerseService();
		});

		$this->app->singleton(
			'app.resource.type.recognition',
			function ($app) {
				return new ResourceRecognitionService();
			}
		);
	}
}
