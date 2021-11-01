<?php

namespace App\Services\ResourceHandling;

use App\Models\PdfFile;
use App\Services\ResourceHandling\Exceptions\InvalidPageNoException;
use App\Services\ResourceHandling\Exceptions\LocalFileDoesNotExistException;
use App\Services\ResourceHandling\Exceptions\RemoteFileDoesNotExistException;
use Howtomakeaturn\PDFInfo\Exceptions\CommandNotFoundException;
use Howtomakeaturn\PDFInfo\Exceptions\OpenOutputException;
use Howtomakeaturn\PDFInfo\Exceptions\OpenPDFException;
use Howtomakeaturn\PDFInfo\Exceptions\OtherException;
use Howtomakeaturn\PDFInfo\Exceptions\PDFPermissionException;
use Howtomakeaturn\PDFInfo\PDFInfo;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use Spatie\PdfToText\Exceptions\PdfNotFound;
use Spatie\PdfToText\Pdf;

class PdfHandlingService {


	protected $fileHandlingService;


	public function __construct(FileHandlingService $fileHandlingService) {
		$this->fileHandlingService = $fileHandlingService;
	}

	/**
	 * @param PdfFile $resource
	 * @return PdfFile
	 */
	public function countPdfPages(PdfFile $resource) {

		if ($resource->hasLocalFile() && $localPdfPath = $resource->getAbsoluteLocalPath()) {

			try {
				$count                = $this->countPdfPagesInFilepath($localPdfPath);
				$resource->page_count = $count;
			} catch (InvalidPageNoException $e) {
				$resource->page_count = FALSE;
				Log::error($e->getMessage(), [$e->getTraceAsString(), 'resource' => $resource->toArray()]);
			}

			$resource->save();
		}

		return $resource;

	}

	/**
	 * @param $localPdfPath
	 * @return mixed
	 * @throws InvalidPageNoException
	 */
	public function countPdfPagesInFilepath($localPdfPath) {

		try {
			Log::info("Start count PDF-Pages");

			$pdf   = new PDFInfo($localPdfPath);
			$count = $pdf->pages;
		} catch (OpenOutputException $e) {
			throw new InvalidPageNoException('Could not extract Page Number from PDF', 0, $e);
		} catch (OpenPDFException $e) {
			throw new InvalidPageNoException('Could not extract Page Number from PDF', 0, $e);
		} catch (PDFPermissionException $e) {
			throw new InvalidPageNoException('Could not extract Page Number from PDF', 0, $e);
		} catch (OtherException $e) {
			throw new InvalidPageNoException('Could not extract Page Number from PDF', 0, $e);
		} catch (CommandNotFoundException $e) {
			throw new InvalidPageNoException('Could not extract Page Number from PDF, because the binary PDFINFO is missing', 0, $e);
		}

		return $count;
	}


	/**
	 * @param PdfFile $resource
	 * @param         $pages
	 * @return Fpdi
	 * @throws InvalidPageNoException
	 * @throws LocalFileDoesNotExistException
	 * @throws RemoteFileDoesNotExistException
	 * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
	 * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
	 * @throws \setasign\Fpdi\PdfReader\PdfReaderException
	 */
	public function extractPdfPages(PdfFile $resource, $pages = NULL) {

		$pdfSrcFilePath = $this->fileHandlingService->getLocalFilePath($resource);

		return $this->extractPdfPagesInFilepath($pdfSrcFilePath, $pages);

	}

	/**
	 * @param      $path
	 * @param null $pages
	 * @return Fpdi
	 * @throws InvalidPageNoException
	 * @throws \setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException
	 * @throws \setasign\Fpdi\PdfParser\Filter\FilterException
	 * @throws \setasign\Fpdi\PdfParser\PdfParserException
	 * @throws \setasign\Fpdi\PdfParser\Type\PdfTypeException
	 * @throws \setasign\Fpdi\PdfReader\PdfReaderException
	 */
	public function extractPdfPagesInFilepath($path, $pages = NULL) {

		$pdf       = new Fpdi();
		$pagecount = $pdf->setSourceFile($path);

		// Add all pages to File
		if ($pages === NULL) {
			$pages = [];
			for ($i = 1; $i <= $pagecount; $i++) {
				$pages[] = $i;
			}
		}


		foreach ($pages as $pageNo) {

			if ($pageNo <= $pagecount && $pageNo > 0) {

				// import a page
				$templateId = $pdf->importPage($pageNo);

				// get the size of the imported page
				$size = $pdf->getTemplateSize($templateId);

				// add a page with the same orientation and size
				$pdf->AddPage($size['orientation'], $size);

				// use the imported page
				$pdf->useTemplate($templateId);

			} else {
				throw new InvalidPageNoException('Page-No ' . $pageNo . ' does not exist in this pdf.');
			}

		}

		return $pdf;
	}

	public function pdfToText($pdfPath, $fromPage = NULL, $toPage = NULL) {

		try {
			$pdfObject = resolve(Pdf::class)->setPdf($pdfPath);
		} catch (PdfNotFound $e) {
			Log::error('PDF-File not found!', [$e->getTraceAsString()]);

			return '';
		}

		$options = [];

		if ($fromPage !== NULL) {
			$options[] = "-f {$fromPage}";

			if ($toPage !== NULL) {
				$options[] = "-l {$toPage}";
			} else {
				$options[] = "-l {$fromPage}";
			}
		}

		return $pdfObject->setOptions($options)->text();

	}


	public function pdfResourceToText(PdfFile $resource, $fromPage = NULL, $toPage = NULL) {

		try {
			$pdfSrcFilePath = $this->fileHandlingService->getLocalFilePath($resource);
		} catch (LocalFileDoesNotExistException $e) {
			Log::error('Local File does not exist', [$e->getTraceAsString()]);

			return '';
		} catch (RemoteFileDoesNotExistException $e) {
			Log::error('Remote File does not exist', [$e->getTraceAsString()]);

			return '';
		}

		return $this->pdfToText($pdfSrcFilePath, $fromPage, $toPage);

	}


}