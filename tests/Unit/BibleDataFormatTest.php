<?php

namespace Tests\Unit;

use App\Services\Bibles\Import\BibleBookMap;
use App\Services\Bibles\Import\OpenBibleData;
use App\Services\Bibles\Import\ScrollmapperCsv;
use App\Services\Bibles\Import\ScrollmapperSource;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class BibleDataFormatTest extends TestCase
{
    public function test_scrollmapper_csv_records_but_does_not_import_empty_placeholders(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'csv-source-');
        $target = tempnam(sys_get_temp_dir(), 'csv-target-');
        try {
            file_put_contents($source, "Book,Chapter,Verse,Text\nIII John,1,15,\nIII John,1,14,Example\n");
            $result = (new ScrollmapperCsv())->normalize($source, $target);
            $this->assertSame(1, $result['count']);
            $this->assertSame(1, $result['books']);
            $this->assertSame(1, $result['placeholders']);
            $this->assertSame('Teilbestand', $result['scope']);
            $this->assertSame([
                ['verse' => 64001014, 'text' => 'Example'],
            ], iterator_to_array((new ScrollmapperCsv())->rows($target), false));
            $this->assertSame(66, BibleBookMap::scrollmapper('Revelation of John'));
        } finally {
            @unlink($source);
            @unlink($target);
        }
    }

    public function test_scrollmapper_csv_rejects_duplicate_reference_without_partial_import(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'csv-source-');
        $target = tempnam(sys_get_temp_dir(), 'csv-target-');
        try {
            file_put_contents($source, "Book,Chapter,Verse,Text\nGenesis,1,1,One\nGenesis,1,1,Two\n");
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Doppelte Scrollmapper-Bibelstelle');
            (new ScrollmapperCsv())->normalize($source, $target);
        } finally {
            @unlink($source);
            @unlink($target);
        }
    }

    public function test_openbible_range_and_negative_votes_have_defined_mapping(): void
    {
        $method = (new \ReflectionClass(OpenBibleData::class))->getMethod('parseSourceLine');
        $row = $method->invoke(new OpenBibleData(), "Gen.1.1\tProv.8.22-Prov.8.30\t-39\n");
        $this->assertSame([1001001, 0, 20008022, 20008030], $row);
    }

    public function test_cross_reference_manifest_rejects_changed_payload(): void
    {
        $directory = sys_get_temp_dir().'/bible-manifest-'.bin2hex(random_bytes(8));
        mkdir($directory);
        config()->set('bible_data.directory', $directory);
        $payload = "1001001\t0\t20008022\t20008030\n";
        file_put_contents($directory.'/'.OpenBibleData::PAYLOAD, $payload);
        file_put_contents($directory.'/'.OpenBibleData::MANIFEST, json_encode([
            'schema' => OpenBibleData::VERSION,
            'dataset_id' => OpenBibleData::ID,
            'source_url' => config('bible_data.openbible_url'),
            'source_date' => '2026-09-28',
            'rights' => 'CC BY', 'payload' => OpenBibleData::PAYLOAD,
            'sha256' => hash('sha256', $payload), 'row_count' => 1,
        ], JSON_THROW_ON_ERROR));
        try {
            $this->assertSame(1, (new OpenBibleData())->validate()['row_count']);
            file_put_contents($directory.'/'.OpenBibleData::PAYLOAD, "1001002\t0\t20008022\t20008030\n");
            $this->expectException(RuntimeException::class);
            (new OpenBibleData())->validate();
        } finally {
            @unlink($directory.'/'.OpenBibleData::PAYLOAD);
            @unlink($directory.'/'.OpenBibleData::MANIFEST);
            @rmdir($directory);
            config()->offsetUnset('bible_data.directory');
        }
    }

    public function test_vetted_catalog_snapshot_marks_unmappable_books_unavailable(): void
    {
        $revision = 'e1b254cef86d0e65b1a5d1a94b8b112d0f296a2c';
        Http::fake([
            '*/commits/master' => Http::response(['sha' => $revision]),
            '*/git/trees/*' => Http::response(['truncated' => false, 'tree' => [
                ['type' => 'blob', 'path' => 'formats/csv/CPDV.csv', 'sha' => str_repeat('a', 40), 'size' => 100],
                ['type' => 'blob', 'path' => 'sources/en/CPDV/README.md', 'sha' => str_repeat('b', 40)],
            ]]),
            '*/translation_list.md' => Http::response("- **CPDV (en)**: Catholic Public Domain Version\n"),
        ]);

        $catalog = (new ScrollmapperSource())->catalog();
        $this->assertCount(1, $catalog);
        $this->assertStringContainsString('Tobit', $catalog['scrollmapper:CPDV']['unavailable_reason']);
        $this->assertSame('Erweiterter Kanon (73 Bücher)', $catalog['scrollmapper:CPDV']['scope']);
        Http::assertSentCount(3);
    }
}
