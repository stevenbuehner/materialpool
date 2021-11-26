<?php

namespace App\Providers;

use App\ResourceLimitations\ResourceLimitationService;
use App\Services\Bundles\BundleQueueService;
use App\Services\Bundles\BundlesService;
use App\Services\ExifReader\ExifMapper;
use App\Services\ExifReader\ExifReader;
use App\Services\ExifReader\ExifReaderInterface;
use App\Services\KeywordHandling\KeywordHandlingService;
use App\Services\MaterialHandling\MaterialDuplicationHandlingService;
use App\Services\MaterialHandling\MaterialHandlingService;
use App\Services\MaterialHandling\PdfMaterialHandlingService;
use App\Services\PreviewGeneration\Generators\DocumentPreviewGenerator;
use App\Services\PreviewGeneration\Generators\ImagePreviewGenerator;
use App\Services\PreviewGeneration\Generators\NoPreviewGenerator;
use App\Services\PreviewGeneration\Generators\PdfPreviewGenerator;
use App\Services\PreviewGeneration\Generators\TextLargePreviewGenerator;
use App\Services\PreviewGeneration\Generators\TextThumbPreviewGenerator;
use App\Services\PreviewGeneration\Generators\VideoPreviewGenerator;
use App\Services\PreviewGeneration\MaterialPreviewService;
use App\Services\PreviewGeneration\ResourcePreviewService;
use App\Services\Processors\ResourceHashProcessor;
use App\Services\ResourceHandling\DocHandlingService;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\PdfHandlingService;
use App\Services\ResourceHandling\ResourceDuplicationHandlingService;
use App\Services\ResourceHandling\ResourceHandlingService;
use App\Services\ResourceHandling\TextHandlingService;
use App\Services\ResourceRecognition\ResourceRecognitionService;
use App\Services\TagExtraction\MaterialExtractionService;
use App\Services\TagExtraction\ResourceHandles\FileExifHandler;
use App\Services\TagExtraction\ResourceHandles\FileNameHandler;
use App\Services\TagExtraction\ResourceHandles\TextContentHandler;
use App\Services\TagExtraction\TagExtractionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use PHPExif\Adapter\Exiftool;
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

			$this->app->register('Illuminate\Translation\TranslationServiceProvider');
		}

		$this->app->singleton('BibleVerseService', function ($app) {
			return new BibleVerseService();
		});


		$this->app->singleton(
			'app.resource.keyword.recognition',
			function ($app) {
				/** @var $app App */
				return $app->make(TagExtractionService::class);
			}
		);

		// Resource Limitation
		$this->app->singleton(ResourceLimitationService::class);


		// +++ Services +++

		// Bundles
		$this->app->singleton(BundlesService::class);
		$this->app->singleton(BundleQueueService::class);

		// Keyword Handling
		$this->app->singleton(KeywordHandlingService::class);

		// Material Handling
		$this->app->singleton(MaterialDuplicationHandlingService::class);
		$this->app->singleton(MaterialHandlingService::class);
		$this->app->singleton(PdfMaterialHandlingService::class);

		// Preview Generation - Generators
		$this->app->singleton(DocumentPreviewGenerator::class);
		$this->app->singleton(ImagePreviewGenerator::class);
		$this->app->singleton(NoPreviewGenerator::class);
		$this->app->singleton(PdfPreviewGenerator::class);
		$this->app->singleton(TextLargePreviewGenerator::class);
		$this->app->singleton(TextThumbPreviewGenerator::class);
		$this->app->singleton(VideoPreviewGenerator::class);

		// Preview Generation
		$this->app->singleton(MaterialPreviewService::class);
		$this->app->singleton(ResourcePreviewService::class);

		// Processors
		$this->app->singleton(ResourceHashProcessor::class);

		// Resource Handling
		$this->app->singleton(DocHandlingService::class);
		$this->app->singleton(FileHandlingService::class);
		$this->app->singleton(PdfHandlingService::class);
		$this->app->singleton(ResourceDuplicationHandlingService::class);
		$this->app->singleton(ResourceHandlingService::class);
		$this->app->singleton(TextHandlingService::class);

		// Resource Recognition
		$this->app->singleton(ResourceRecognitionService::class);

		// Tag Extraction - Handler
		$this->app->singleton(FileExifHandler::class);
		$this->app->singleton(FileNameHandler::class);
		$this->app->singleton(TextContentHandler::class);

		$this->app->singleton(MaterialExtractionService::class);
		$this->app->singleton(TagExtractionService::class);


		$this->app->singleton(ExifReaderInterface::class, function ($app) {
			/** @var $app App */

			$adapter = new Exiftool($options = [
				'toolPath' => realpath(base_path() . '/vendor/phpexiftool/exiftool/exiftool')
			]);
			$adapter->setMapper(new ExifMapper());

			return new ExifReader($adapter);
		});

		Carbon::setLocale(config('app.locale'));
	}
}
