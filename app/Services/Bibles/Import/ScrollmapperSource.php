<?php

namespace App\Services\Bibles\Import;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class ScrollmapperSource implements TranslationSource
{
    public const VERSION = 2;

    private string $revision;
    private array $blobs = [];
    private array $catalog = [];

    public function refresh(): void
    {
        $this->catalog = [];
        $this->blobs = [];
        unset($this->revision);
    }

    public function catalog(bool $loadRights = false): array
    {
        if ($this->catalog === []) {
            $repository = config('bible_data.scrollmapper_repository');
            $commit = $this->json("https://api.github.com/repos/{$repository}/commits/master");
            $this->revision = $commit['sha'] ?? '';
            if (! preg_match('/^[a-f0-9]{40}$/', $this->revision)) {
                throw new RuntimeException('Scrollmapper-Commit konnte nicht fixiert werden.');
            }
            $tree = $this->json("https://api.github.com/repos/{$repository}/git/trees/{$this->revision}?recursive=1");
            if (($tree['truncated'] ?? true) || ! is_array($tree['tree'] ?? null)) {
                throw new RuntimeException('Scrollmapper-Katalog ist unvollständig.');
            }
            foreach ($tree['tree'] as $entry) {
                if (($entry['type'] ?? '') === 'blob' && preg_match('~^formats/csv/([A-Za-z0-9_-]+)\.csv$~', $entry['path'] ?? '', $match)) {
                    $this->blobs[$match[1]] = ['sha' => $entry['sha'], 'path' => $entry['path'], 'size' => $entry['size'] ?? 0];
                }
                if (($entry['type'] ?? '') === 'blob' && preg_match('~^sources/([a-z0-9-]+)/([A-Za-z0-9_-]+)/README\.md$~', $entry['path'] ?? '', $match)) {
                    $readmes[$match[2]] = $entry['path'];
                }
            }
            $list = $this->get("https://raw.githubusercontent.com/{$repository}/{$this->revision}/docs/main_readme/translation_list.md", 1024 * 1024);
            foreach (explode("\n", $list) as $line) {
                if (! preg_match('/^- \*\*([A-Za-z0-9_-]+) \(([^)]+)\)\*\*: (.+)$/u', $line, $match)) {
                    continue;
                }
                [$all, $code, $language, $title] = $match;
                $id = 'scrollmapper:'.$code;
                $this->catalog[$id] = [
                    'id' => $id, 'code' => $code, 'language' => $language, 'title' => trim($title),
                    'path' => $this->blobs[$code]['path'] ?? null,
                    'blob' => $this->blobs[$code]['sha'] ?? null,
                    'size' => $this->blobs[$code]['size'] ?? null,
                    'readme' => $readmes[$code] ?? null,
                    'rights' => null,
                    'source_url' => "https://github.com/{$repository}/tree/{$this->revision}/sources",
                ];
            }
            if ($this->catalog === []) {
                throw new RuntimeException('Scrollmapper-Katalog enthält keine Übersetzungen.');
            }
            $this->applySnapshot();
        }
        if ($loadRights) {
            $this->loadRights();
        }

        return $this->catalog;
    }

    public function metadata(string $id): array
    {
        $this->catalog();
        if (! isset($this->catalog[$id])) {
            throw new RuntimeException("Übersetzung im fixierten Katalog nicht vorhanden: {$id}");
        }
        $entry = $this->catalog[$id];
        if ($entry['readme'] !== null && $entry['rights'] === null) {
            $repository = config('bible_data.scrollmapper_repository');
            $readme = $this->get("https://raw.githubusercontent.com/{$repository}/{$this->revision}/{$entry['readme']}", 256 * 1024);
            $this->applyReadme($id, $readme);
        }

        return $this->catalog[$id];
    }

    public function revision(): string
    {
        $this->catalog();

        return $this->revision;
    }

    public function normalizationVersion(): int
    {
        return self::VERSION;
    }

    public function download(array $entry, string $target): void
    {
        if ($entry['path'] === null || ! preg_match('~^formats/csv/[A-Za-z0-9_-]+\.csv$~', $entry['path'])) {
            throw new RuntimeException("CSV-Export fehlt: {$entry['id']}");
        }
        if (($entry['size'] ?? 0) > config('bible_data.max_translation_bytes')) {
            throw new RuntimeException("CSV-Export ist zu groß: {$entry['id']}");
        }
        $repository = config('bible_data.scrollmapper_repository');
        $url = "https://raw.githubusercontent.com/{$repository}/{$this->revision}/{$entry['path']}";
        $response = Http::withOptions(['allow_redirects' => false])->timeout(90)->connectTimeout(10)
            ->retry(2, 500)->sink($target)->get($url);
        if (! $response->successful() || ! is_file($target) || filesize($target) > config('bible_data.max_translation_bytes')) {
            throw new RuntimeException("Scrollmapper-Download fehlgeschlagen: {$entry['id']}");
        }
        $blob = 'blob '.filesize($target)."\0".file_get_contents($target);
        if (! hash_equals($entry['blob'], sha1($blob))) {
            throw new RuntimeException("Git-Blob-Prüfsumme stimmt nicht: {$entry['id']}");
        }
    }

    private function json(string $url): array
    {
        $body = $this->get($url, 8 * 1024 * 1024);
        $result = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($result)) {
            throw new RuntimeException('Ungültige Antwort der Scrollmapper-API.');
        }

        return $result;
    }

    private function loadRights(): void
    {
        $repository = config('bible_data.scrollmapper_repository');
        $pending = array_filter($this->catalog, fn (array $entry): bool => $entry['readme'] !== null && $entry['rights'] === null);
        foreach (array_chunk($pending, 12, true) as $batch) {
            $responses = Http::pool(function (Pool $pool) use ($batch, $repository): array {
                $requests = [];
                foreach ($batch as $id => $entry) {
                    $url = "https://raw.githubusercontent.com/{$repository}/{$this->revision}/{$entry['readme']}";
                    $requests[$id] = $pool->as($id)->withOptions(['allow_redirects' => false])
                        ->timeout(30)->connectTimeout(10)->retry(2, 500)->get($url);
                }

                return $requests;
            });
            foreach ($batch as $id => $entry) {
                $response = $responses[$id] ?? null;
                if (! $response || ! $response->successful() || strlen($response->body()) > 256 * 1024) {
                    throw new RuntimeException("Rechteangabe nicht abrufbar: {$id}");
                }
                $this->applyReadme($id, $response->body());
            }
        }
    }

    private function applySnapshot(): void
    {
        $path = config_path('bible_catalog_snapshot.json');
        $snapshot = is_file($path) ? json_decode(file_get_contents($path), true) : null;
        if (! is_array($snapshot) || ($snapshot['source_commit'] ?? null) !== $this->revision) {
            foreach ($this->catalog as &$entry) {
                $entry['scope'] = null;
                $entry['unavailable_reason'] = null;
            }
            unset($entry);

            return;
        }
        foreach ($this->catalog as $id => &$entry) {
            $facts = $snapshot['entries'][$entry['code']] ?? null;
            if (! is_array($facts)) {
                $entry['scope'] = null;
                $entry['unavailable_reason'] = null;
                continue;
            }
            $entry['scope'] = $facts['books'] > 66
                ? "Erweiterter Kanon ({$facts['books']} Bücher)"
                : ($facts['books'] === 66 ? 'Vollbibel' : 'Teilbestand');
            $entry['unavailable_reason'] = $facts['verses'] === 0
                ? 'Quell-CSV enthält keine Textverse.'
                : (($facts['unsupported_books'] ?? []) !== []
                    ? 'Nicht abbildbare Bücher: '.implode(', ', $facts['unsupported_books'])
                    : null);
            $entry['book_count'] = $facts['books'];
        }
        unset($entry);
    }

    private function applyReadme(string $id, string $readme): void
    {
        $entry = $this->catalog[$id];
        $entry['rights'] = preg_match('/^\*\*License:\*\*\s*(.+)$/mi', $readme, $match)
            ? trim($match[1]) : 'nicht angegeben';
        $repository = config('bible_data.scrollmapper_repository');
        $entry['source_url'] = "https://github.com/{$repository}/blob/{$this->revision}/{$entry['readme']}";
        $this->catalog[$id] = $entry;
    }

    private function get(string $url, int $limit): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! in_array($host, ['api.github.com', 'raw.githubusercontent.com'], true)) {
            throw new RuntimeException('Unzulässiger Scrollmapper-Host.');
        }
        $response = Http::withOptions(['allow_redirects' => false])->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->timeout(30)->connectTimeout(10)->retry(2, 500)->get($url);
        if (! $response->successful() || strlen($response->body()) > $limit) {
            throw new RuntimeException('Scrollmapper-Abruf fehlgeschlagen oder zu groß (HTTP '.$response->status().').');
        }

        return $response->body();
    }
}
