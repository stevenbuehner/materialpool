<?php

namespace App\Providers;

use App\ResourceLimitations\ResourceLimitationService;
use App\Services\Bundles\BundleQueueService;
use App\Services\Bundles\BundlesService;
use App\Services\KeywordHandling\KeywordHandlingService;
use App\Services\MaterialHandling\MaterialDuplicationHandlingService;
use App\Services\MaterialHandling\MaterialHandlingService;
use App\Services\PreviewGeneration\Generators\ImagePreviewGenerator;
use App\Services\PreviewGeneration\Generators\NoPreviewGenerator;
use App\Services\PreviewGeneration\Generators\PdfPreviewGenerator;
use App\Services\PreviewGeneration\Generators\TextLargePreviewGenerator;
use App\Services\PreviewGeneration\Generators\TextThumbPreviewGenerator;
use App\Services\PreviewGeneration\Generators\VideoPreviewGenerator;
use App\Services\PreviewGeneration\ResourcePreviewService;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\PdfHandlingService;
use App\Services\ResourceHandling\ResourceDuplicationHandlingService;
use App\Services\ResourceRecognition\ResourceRecognitionService;
use App\Services\TagExtraction\MaterialExtractionService;
use App\Services\TagExtraction\ResourceHandles\FileNameHandler;
use App\Services\TagExtraction\TagExtractionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use PHPExiftool\Reader;
use Psr\Log\LoggerInterface;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

class AppServiceProvider extends ServiceProvider {
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot() {
		Schema::defaultStringLength(191);

		// Workaround für Passport 6
		Passport::withoutCookieSerialization();
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

		$this->app->singleton(FileHandlingService::class);
		$this->app->singleton(PdfHandlingService::class);
		$this->app->singleton(ResourceDuplicationHandlingService::class);
		$this->app->singleton(ResourceLimitationService::class);

		$this->app->singleton(FileNameHandler::class);
		$this->app->singleton(KeywordHandlingService::class);

		$this->app->singleton(MaterialExtractionService::class);
		$this->app->singleton(MaterialHandlingService::class);
		$this->app->singleton(MaterialDuplicationHandlingService::class);

		$this->app->singleton(BundlesService::class);
		$this->app->singleton(BundleQueueService::class);


		// ResourcePreview Generators as Singletons
		$this->app->singleton(ResourcePreviewService::class);
		$this->app->singleton(ImagePreviewGenerator::class);
		$this->app->singleton(NoPreviewGenerator::class);
		$this->app->singleton(PdfPreviewGenerator::class);
		$this->app->singleton(TextLargePreviewGenerator::class);
		$this->app->singleton(TextThumbPreviewGenerator::class);
		$this->app->singleton(VideoPreviewGenerator::class);

		$this->app->singleton('PHPExiftool\Reader', function ($app) {
			$logger = resolve(LoggerInterface::class);
			$reader = Reader::create($logger);

			return $reader;
		});

		Carbon::setLocale(config('app.locale'));
	}
}
