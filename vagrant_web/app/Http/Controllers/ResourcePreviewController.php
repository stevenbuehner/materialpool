<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidResourceTypeException;
use App\Models\DocumentFile;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Services\PreviewGeneration\ResourcePreviewService;
use App\Services\PreviewGeneration\PreviewSize;
use Illuminate\Http\Request;

class ResourcePreviewController {

	protected $previewService;
	public function __construct(ResourcePreviewService $previewService) {
		$this->previewService = $previewService;
	}

	/**
	 * @param Resource $resource
	 * @param int $width
	 * @param int $height
	 * @return \Illuminate\Http\Response
	 */
	public function getImage(Request $request, Resource $resource, $width = NULL, $height = NULL) {
		$imageData = $this->previewService->getCachedImageData(
			$resource,
			PreviewSize::constrained($width, $height)
		);

		return $this->imageResponse($request, $imageData);

	}

	/**
	 * @param Resource $resource
	 * @param $page
	 * @param NULL|'refresh' $clearCache
	 * @return mixed
	 * @throws InvalidResourceTypeException
	 */
	public function getPageImage(Request $request, Resource $resource, $page, $clearCache = NULL) {

		if (!$resource instanceof PdfFile && !$resource instanceof DocumentFile) {
			throw new InvalidResourceTypeException('Only PDF and DOC resources can have page-preview images');
		}

		$imageData = $this->previewService->getCachedImageData(
			$resource,
			PreviewSize::constrained(
				$request->has('width') ? $request->integer('width') : NULL,
				$request->has('height') ? $request->integer('height') : NULL
			),
			$page,
			$clearCache === 'refresh'
		);

		return $this->imageResponse($request, $imageData);
	}

	protected function imageResponse(Request $request, string $imageData) {
		$response = response($imageData, 200, ['Content-Type' => 'image/jpeg']);
		$response->setEtag(hash('sha256', $imageData));
		$response->isNotModified($request);

		return $response;

	}


}
