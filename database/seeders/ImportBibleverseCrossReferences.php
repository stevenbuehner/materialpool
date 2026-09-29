<?php

namespace Database\Seeders;

use App\Models\BibleverseCrossReference;
use Illuminate\Database\Seeder;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class ImportBibleverseCrossReferences extends Seeder
{
    protected function sourcePath(): string
    {
        return database_path('seeders/data/cross_references/cross_reference-mysql.sql');
    }

    public function run(): void
    {
        $filePath = $this->sourcePath();

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new RuntimeException('Cross-reference SQL file is missing or unreadable: '.$filePath);
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new RuntimeException('Could not open cross-reference SQL file: '.$filePath);
        }

        $output = new ConsoleOutput();
        $output->writeln('<info>Start importing cross references</info>');

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat(' %current% [%bar%] %elapsed:6s% %memory:6s%');
        $progressBar->minSecondsBetweenRedraws(0.5);
        $progressBar->maxSecondsBetweenRedraws(2);

        $importing = false;
        $batch = [];
        $batchSize = 100;

        try {
            while (($line = fgets($handle)) !== false) {
                if (!$importing) {
                    if (!str_starts_with($line, 'INSERT INTO `cross_reference`')) {
                        continue;
                    }

                    // A rerun replaces the imported reference set.
                    $countDropped = BibleverseCrossReference::query()->delete();

                    if ($countDropped > 0) {
                        $output->writeln("<comment>$countDropped entries were deleted before import</comment>");
                    }

                    $importing = true;
                    $progressBar->start();
                    continue;
                }

                if (preg_match('~^\((?<source>\d+),\s*(?<relevance>\d+),\s*(?<target_from>\d+),\s*(?<target_to>\d+)\)[,;]\s*$~', $line, $match) === 1) {
                    $batch[] = [
                        'source' => $match['source'],
                        'relevance' => $match['relevance'],
                        'target_from' => $match['target_from'],
                        'target_to' => $match['target_to'],
                    ];
                } elseif (str_starts_with(ltrim($line), '(')) {
                    throw new RuntimeException('Invalid cross-reference row in SQL file.');
                }

                if (count($batch) >= $batchSize) {
                    BibleverseCrossReference::insert($batch);
                    $progressBar->advance(count($batch));
                    $batch = [];
                }
            }

            if (!$importing) {
                throw new RuntimeException('Cross-reference INSERT statement was not found in SQL file.');
            }

            if ($batch !== []) {
                BibleverseCrossReference::insert($batch);
                $progressBar->advance(count($batch));
            }
        } finally {
            fclose($handle);
        }

        $progressBar->finish();
        $output->writeln('<info>Import finished</info>');
    }
}
