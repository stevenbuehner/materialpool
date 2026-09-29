<?php

namespace App\Services\Bibles\Import;

interface TranslationSource
{
    public function catalog(bool $loadRights = false): array;

    public function metadata(string $id): array;

    public function revision(): string;

    public function normalizationVersion(): int;

    public function download(array $entry, string $target): void;
}
