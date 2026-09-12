<?php

namespace App\Services\PreviewGeneration\Generators;

use App\Models\DocumentFile;
use App\Models\Resource as ResourceEntity;
use App\ResourceLimitations\ResourceLimitationInterface;
use App\Services\PreviewGeneration\Exceptions\NotPreviewAbleException;
use App\Services\PreviewGeneration\Interfaces\PreviewGeneratorInterface;
use App\Services\ResourceHandling\Exceptions\LocalFileDoesNotExistException;
use App\Services\ResourceHandling\Exceptions\RemoteFileDoesNotExistException;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Image as Image;
use Intervention\Image\Size;
use NcJoes\OfficeConverter\OfficeConverter;
use NcJoes\OfficeConverter\OfficeConverterException;

class DocumentPreviewGenerator extends PdfPreviewGenerator implements PreviewGeneratorInterface {


	/**
	 * @param ResourceEntity $resource
	 * @return mixed
	 */
	public function imagePreviewAble(ResourceEntity $resource) {

		try {
			$this->getTemporaryPdfFromDocument($resource);
		} catch (NotPreviewAbleException $e) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param DocumentFile $resource
	 * @return string|null
	 * @throws NotPreviewAbleException
	 */
	public function getTemporaryPdfFromDocument(DocumentFile $resource) {

		try {
			$resourcePath = $this->resourceFileService->getLocalFilePath($resource);
		} catch (LocalFileDoesNotExistException $e) {
			throw new NotPreviewAbleException('Could not get local file path', 0, $e);
		} catch (RemoteFileDoesNotExistException $e) {
			throw new NotPreviewAbleException('Could not get remote file path', 0, $e);
		}

		$basename    = pathinfo($resourcePath, PATHINFO_BASENAME);
		$tempPdfName = $basename . '.pdf';

		$tempPdfDir  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdf_preview';
		$tempPdfPath = $tempPdfDir . DIRECTORY_SEPARATOR . $tempPdfName;

		if (file_exists($tempPdfPath)) {
			return $tempPdfPath;
		}


		if (!file_exists($tempPdfDir)) {
			mkdir($tempPdfDir);
		}


		try {
			Log::info('Start Converting Document to PDF');
			$converter   = new OfficeConverter($resourcePath, $tempPdfDir);
			$tempPdfPath = $converter->convertTo($tempPdfName);
		} catch (OfficeConverterException $e) {
			throw new NotPreviewAbleException('Pdf could not be created due to office exepction: ' . $e->getMessage(), 0, $e);
		}

		return $tempPdfPath;

	}

	/**
	 * @param ResourceEntity $resource
	 * @param Size $size
	 * @param null|int $page (optional) Starting from 1 to ... x
	 * @return Image
	 * @throws NotPreviewAbleException
	 */
	public function getImagePreview(ResourceEntity $resource, Size $size, $page = 1) {

		if (!$resource instanceof DocumentFile) {
			throw new NotPreviewAbleException('DocumentResource-Type required');
		}

		$tempPath = $this->getTemporaryPdfFromDocument($resource);
		$image    = $this->getImagePreviewFromPdfPath($tempPath, $size, $page);

		return $image;
	}

	/**
	 * @param ResourceEntity $resource
	 * @return mixed
	 */
	public function htmlPreviewAble(ResourceEntity $resource) {
		return FALSE;
	}

	/**
	 * @param ResourceEntity $resource
	 * @param ResourceLimitationInterface $limitation
	 * @param string|null $context
	 * @return string|false
	 * @throws NotPreviewAbleException
	 */
	public function renderHTMLPreview(ResourceEntity $resource, ?ResourceLimitationInterface $limitation = NULL, $context = NULL) {
		throw new NotPreviewAbleException();
	}

}
