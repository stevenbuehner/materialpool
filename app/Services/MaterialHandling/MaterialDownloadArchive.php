<?php

namespace App\Services\MaterialHandling;

use App\Models\AudioFile;
use App\Models\DocumentFile;
use App\Models\File as ResourceFile;
use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Url;
use App\Models\User;
use App\Models\VideoFile;
use App\ResourceLimitations\AnkerLimitation;
use App\ResourceLimitations\PageLimitation;
use App\ResourceLimitations\TimeLimitation;
use App\Services\PreviewGeneration\Generators\DocumentPreviewGenerator;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\PdfHandlingService;
use App\Services\TagExtraction\ResourceHandles\TextContentInterface;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class MaterialDownloadArchive {
    public function __construct(
        private readonly MaterialDownloadStore $store,
        private readonly FileHandlingService $files,
        private readonly PdfHandlingService $pdfs,
    ) {}

    public function build(Material $material, User $user, string $token): void {
        $resources = $material->resources()->visibleTo($user)->get();
        if ($resources->isEmpty()) {
            throw new RuntimeException('No readable resources are available for this material.');
        }

        File::ensureDirectoryExists($this->store->root().'/work');
        $workPath = $this->store->workPath($token);
        $zip = new ZipArchive();
        if ($zip->open($workPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new RuntimeException('Could not open material download archive.');
        }

        $temporaryFiles = [];
        $closed = FALSE;
        try {
            foreach ($resources as $resource) {
                $limitation = $resource->pivot->limitation;
                $name = 'resource-'.$resource->id.'-'.basename(str_replace('\\', '/', (string)($resource->original_filename ?: $resource->id)));
                if ($resource instanceof ResourceFile) {
                    if (!$resource->hasLocalFile()) {
                        if ($limitation !== NULL || !$resource->hasRemoteFile()) {
                            throw new RuntimeException('Resource has no local file for download.');
                        }
                        if (!$zip->addFromString('resource-'.$resource->id.'.url', (string)$resource->remote_path)) {
                            throw new RuntimeException('Could not add remote resource link to archive.');
                        }
                        continue;
                    }
                    $path = $this->files->getLocalFilePath($resource);
                    if (!is_file($path)) {
                        throw new RuntimeException('A resource file is missing.');
                    }
                    if ($limitation instanceof PageLimitation) {
                        if ($limitation->getPages() === []) {
                            throw new RuntimeException('Page limitation has no pages.');
                        }
                        if (!$resource instanceof PdfFile && !$resource instanceof DocumentFile) {
                            throw new RuntimeException('Page limitation is not applicable to this resource.');
                        }
                        $source = $resource instanceof DocumentFile
                            ? resolve(DocumentPreviewGenerator::class)->getTemporaryPdfFromDocument($resource)
                            : $path;
                        $path = $this->temporaryPath('.pdf');
                        $temporaryFiles[] = $path;
                        $this->pdfs->extractPdfPagesInFilepath($source, $limitation->getPages())->Output('F', $path);
                        $name = pathinfo($name, PATHINFO_FILENAME).'.pdf';
                    } elseif ($limitation instanceof TimeLimitation) {
                        if (!$resource instanceof AudioFile && !$resource instanceof VideoFile) {
                            throw new RuntimeException('Time limitation is not applicable to this resource.');
                        }
                        $duration = $limitation->getEnd() - $limitation->getStart();
                        if ($limitation->getStart() < 0 || $duration <= 0) {
                            throw new RuntimeException('Invalid time limitation.');
                        }
                        $extension = $resource instanceof VideoFile ? '.mp4' : '.mp3';
                        $output = $this->temporaryPath($extension);
                        $temporaryFiles[] = $output;
                        $codec = $resource instanceof VideoFile ? ['-c:v', 'libx264', '-c:a', 'aac'] : ['-vn', '-c:a', 'libmp3lame'];
                        (new Process(array_merge([
                            '/usr/bin/ffmpeg', '-nostdin', '-y', '-ss', (string)$limitation->getStart(),
                            '-i', $path, '-t', (string)$duration,
                        ], $codec, [$output])))->setTimeout(3600)->mustRun();
                        $path = $output;
                        $name = pathinfo($name, PATHINFO_FILENAME).$extension;
                    } elseif ($limitation !== NULL) {
                        throw new RuntimeException('Unsupported file limitation.');
                    }
                    if (!$zip->addFile($path, $name)) {
                        throw new RuntimeException('Could not add resource to archive.');
                    }
                } elseif ($resource instanceof TextContentInterface) {
                    $content = $resource->getContent();
                    if ($limitation instanceof AnkerLimitation && $resource instanceof Url) {
                        $content = preg_replace('/#.*$/', '', $content).'#'.ltrim($limitation->getAnker(), '#');
                    } elseif ($limitation !== NULL) {
                        throw new RuntimeException('Unsupported text limitation.');
                    }
                    if (!$zip->addFromString('resource-'.$resource->id.'.txt', $content)) {
                        throw new RuntimeException('Could not add text resource to archive.');
                    }
                } else {
                    if ($limitation !== NULL) {
                        throw new RuntimeException('Unsupported resource limitation.');
                    }
                    if (!$zip->addFromString('resource-'.$resource->id.'.txt', implode("\n", [
                        'Resource '.$resource->id,
                        'Type: '.$resource->type,
                        (string)$resource->notes,
                        (string)$resource->remote_path,
                    ]))) {
                        throw new RuntimeException('Could not add resource description to archive.');
                    }
                }
            }
            $closedSuccessfully = $zip->close();
            $closed = TRUE;
            if (!$closedSuccessfully) {
                throw new RuntimeException('Could not close material download archive.');
            }
            $this->store->publish($token);
        } catch (\Throwable $exception) {
            if (!$closed) {
                $zip->close();
            }
            File::delete([$workPath, $this->store->readyPath($token)]);
            throw $exception;
        } finally {
            File::delete($temporaryFiles);
        }
    }

    private function temporaryPath(string $extension): string {
        $path = tempnam(sys_get_temp_dir(), 'material-download-');
        if ($path === FALSE) {
            throw new RuntimeException('Could not create temporary download file.');
        }
        $target = $path.$extension;
        if (!rename($path, $target)) {
            throw new RuntimeException('Could not prepare temporary download file.');
        }
        return $target;
    }
}
