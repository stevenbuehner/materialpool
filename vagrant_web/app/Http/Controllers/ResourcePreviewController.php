<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidResourceTypeException;
use App\Models\DocumentFile;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Services\PreviewGeneration\ResourcePreviewService;
use App\Services\ResourceHandling\FileHandlingService;
use Intervention\Image\Size;

class ResourcePreviewController {

	protected $previewService;
	protected $fileHandlingService;

	public function __construct(ResourcePreviewService $previewService, FileHandlingService $fhs) {
		$this->previewService      = $previewService;
		$this->fileHandlingService = $fhs;
	}

	/**
	 * @param Resource $resource
	 * @param int $width
	 * @param int $height
	 * @return \Illuminate\Http\Response
	 */
	public function getImage(Resource $resource, $width = 1024, $height = 1024) {

		$size = new Size(
			min($width, config('app.resource.preview.maxWidth')),
			min($height, config('app.resource.preview.maxHeight'))
		);

		$image = $this->previewService->getCachedImage($resource, $size);

		return $image->response(config('app.preview.outputFormat'));

	}

	public function getPageImage(Resource $resource, $page) {

		if (!$resource instanceof PdfFile && !$resource instanceof DocumentFile) {
			throw new InvalidResourceTypeException('Only PDF and DOC resources can have page-preview images');
		}

		$size = new Size(
			config('app.resource.preview.maxWidth'),
			config('app.resource.preview.maxHeight')
		);

		$image = $this->previewService->getCachedImage($resource, $size, $page);

		return $image->response(config('app.preview.outputFormat'));

	}
}
