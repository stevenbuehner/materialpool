<?php

namespace App\Providers;

use App\ResourceLimitations\ResourceLimitationService;
use App\Services\Bundles\BundleQueueService;
use App\Services\Bundles\BundlesService;
use App\Services\ContextSearch\EmbeddingProfile;
use App\Services\ContextSearch\ContextSearchResourceIndexer;
use App\Services\ContextSearch\Extraction\OcrProcessor;
use App\Services\ContextSearch\Extraction\OcrQualityGate;
use App\Services\ContextSearch\Extraction\ResourceTextExtractor;
use App\Services\ContextSearch\Extraction\TesseractOcrProcessor;
use App\Services\ContextSearch\Ollama\OllamaEmbeddingPool;
use App\Services\ContextSearch\Ollama\OllamaServerConfiguration;
use App\Services\ContextSearch\Qdrant\HttpQdrantClient;
use App\Services\ContextSearch\Qdrant\QdrantClient;
use App\Services\ContextSearch\Qdrant\QdrantCollectionProvisioner;
use App\Services\ContextSearch\TextChunker;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use PHPExif\Adapter\Exiftool;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

class AppServiceProvider extends ServiceProvider {
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot(): void {
		Schema::defaultStringLength(191);
		File::ensureDirectoryExists(config('cache.stores.previewimages.path'));
	}

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register(): void {
		if ($this->app->environment() == 'local') {
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

		// Context Search
		$this->app->singleton(QdrantClient::class, fn (): QdrantClient => new HttpQdrantClient(
			url: (string) config('context_search.qdrant.url'),
			apiKey: config('context_search.qdrant.api_key'),
			connectTimeout: (int) config('context_search.qdrant.connect_timeout'),
			timeout: (int) config('context_search.qdrant.timeout'),
		));
		$this->app->singleton(QdrantCollectionProvisioner::class, fn ($app): QdrantCollectionProvisioner => new QdrantCollectionProvisioner(
			client: $app->make(QdrantClient::class),
			collectionPrefix: (string) config('context_search.qdrant.collection_prefix'),
			activeAlias: (string) config('context_search.qdrant.active_alias'),
			distance: (string) config('context_search.qdrant.distance'),
			vectorsOnDisk: (bool) config('context_search.qdrant.vectors_on_disk'),
			payloadOnDisk: (bool) config('context_search.qdrant.payload_on_disk'),
		));
		$this->app->singleton(EmbeddingProfile::class, fn (): EmbeddingProfile => EmbeddingProfile::fromConfiguration(
			(array) config('context_search.embedding'),
		));
		$this->app->singleton(OllamaEmbeddingPool::class, fn ($app): OllamaEmbeddingPool => new OllamaEmbeddingPool(
			servers: OllamaServerConfiguration::parse(
				(string) config('context_search.ollama.servers'),
				(string) config('context_search.ollama.api_keys'),
			),
			profile: $app->make(EmbeddingProfile::class),
			cache: $app['cache.store'],
			connectTimeout: (int) config('context_search.ollama.connect_timeout'),
			timeout: (int) config('context_search.embedding.timeout'),
			failureThreshold: (int) config('context_search.ollama.failure_threshold'),
			circuitCooldown: (int) config('context_search.ollama.circuit_cooldown'),
		));
        $this->app->singleton(OcrProcessor::class, fn (): OcrProcessor => new TesseractOcrProcessor(
			languages: (string) config('context_search.indexing.ocr_languages'),
			timeout: (int) config('context_search.indexing.ocr_timeout'),
            renderDpi: (int) config('context_search.indexing.ocr_render_dpi'),
            pageSegmentationMode: (int) config('context_search.indexing.ocr_page_segmentation_mode'),
            engineVersion: (string) config('context_search.indexing.ocr_engine_version'),
        ));
        $this->app->singleton(OcrQualityGate::class, fn (): OcrQualityGate => new OcrQualityGate(
            minimumMeanConfidence: (float) config('context_search.indexing.ocr_quality_minimum_mean_confidence'),
            minimumRecognizedWords: (int) config('context_search.indexing.ocr_quality_minimum_recognized_words'),
            minimumAlphanumericRatio: (float) config('context_search.indexing.ocr_quality_minimum_alphanumeric_ratio'),
            maximumReplacementCharacterRatio: (float) config('context_search.indexing.ocr_quality_maximum_replacement_character_ratio'),
        ));
		$this->app->singleton(ResourceTextExtractor::class, fn ($app): ResourceTextExtractor => new ResourceTextExtractor(
			pdfs: $app->make(PdfHandlingService::class),
			files: $app->make(FileHandlingService::class),
            ocr: $app->make(OcrProcessor::class),
            nativeTextMinimumCharacters: (int) config('context_search.indexing.pdf_native_text_minimum_characters'),
            qualityGate: $app->make(OcrQualityGate::class),
		));
		$this->app->singleton(TextChunker::class, fn (): TextChunker => new TextChunker(
			targetCharacters: (int) config('context_search.chunking.target_characters'),
			overlapCharacters: (int) config('context_search.chunking.overlap_characters'),
		));
		$this->app->singleton(ContextSearchResourceIndexer::class, fn ($app): ContextSearchResourceIndexer => new ContextSearchResourceIndexer(
			extractor: $app->make(ResourceTextExtractor::class),
			chunker: $app->make(TextChunker::class),
			embeddings: $app->make(OllamaEmbeddingPool::class),
			qdrant: $app->make(QdrantClient::class),
			embeddingBatchSize: (int) config('context_search.indexing.embedding_batch_size'),
		));

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
