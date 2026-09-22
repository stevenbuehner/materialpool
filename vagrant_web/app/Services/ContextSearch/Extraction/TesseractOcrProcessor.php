<?php

namespace App\Services\ContextSearch\Extraction;

use RuntimeException;
use Symfony\Component\Process\Process;

final class TesseractOcrProcessor implements OcrProcessor
{
    public function __construct(
        private readonly string $languages,
        private readonly int $timeout,
    ) {
    }

    public function extractPage(string $pdfPath, int $pageNumber): OcrResult
    {
        $temporaryDirectory = sys_get_temp_dir().'/materialpool-ocr-'.bin2hex(random_bytes(8));

        if (! mkdir($temporaryDirectory, 0700) && ! is_dir($temporaryDirectory)) {
            throw new RuntimeException('Temporary OCR directory could not be created.');
        }

        $imageBase = $temporaryDirectory.'/page';
        $imagePath = $imageBase.'.png';

        try {
            $this->run(['pdftoppm', '-f', (string) $pageNumber, '-l', (string) $pageNumber, '-r', '200', '-png', '-singlefile', $pdfPath, $imageBase]);
            $text = $this->run(['tesseract', $imagePath, 'stdout', '-l', $this->languages, '--psm', '3']);
            $tsv = $this->run(['tesseract', $imagePath, 'stdout', '-l', $this->languages, '--psm', '3', 'tsv']);

            return new OcrResult($text, $this->confidence($tsv), 'tesseract-5');
        } finally {
            @unlink($imagePath);
            @rmdir($temporaryDirectory);
        }
    }

    /** @param array<int, string> $command */
    private function run(array $command): string
    {
        $process = new Process($command);
        $process->setTimeout($this->timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('OCR command failed without exposing document contents.');
        }

        return $process->getOutput();
    }

    private function confidence(string $tsv): float
    {
        $confidences = [];

        foreach (array_slice(preg_split('/\R/', $tsv) ?: [], 1) as $line) {
            $columns = explode("\t", $line);
            $confidence = $columns[10] ?? null;

            if (is_numeric($confidence) && (float) $confidence >= 0) {
                $confidences[] = (float) $confidence;
            }
        }

        return $confidences === [] ? 0.0 : array_sum($confidences) / count($confidences) / 100;
    }
}
