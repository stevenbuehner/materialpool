<?php

namespace App\Providers;

use App\Services\ResourceRecognition\ResourceRecognitionService;
use App\Services\TagExtraction\MaterialExtractionService;
use App\Services\TagExtraction\ResourceHandles\FileNameHandler;
use App\Services\TagExtraction\TagExtractionService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use PHPExiftool\Reader;
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

		$this->app->singleton(
			'app.resource.keyword.recognition',
			function ($app) {
				/** @var $app App */
				return $app->make(TagExtractionService::class);
			}
		);

		$this->app->singleton(FileNameHandler::class);
		$this->app->singleton(MaterialExtractionService::class);

		$this->app->singleton('PHPExiftool\Reader', function ($app) {
			$logger = new Logger('exiftool');
			$reader = Reader::create($logger);

			return $reader;
		});
	}
}
