<?php

namespace Tests\Feature;

use App\Services\Bibles\Import\OpenBibleData;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class BibleDataImportCommandTest extends TestCase
{
    use DatabaseMigrations;

    private string $fixtureDirectory;
    private string $fakeLicense = 'Public Domain';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureDirectory = sys_get_temp_dir().'/bible-import-test-'.bin2hex(random_bytes(8));
        mkdir($this->fixtureDirectory);
        config()->set('bible_data.directory', $this->fixtureDirectory);
        config()->set('cache.default', 'array');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->fixtureDirectory.'/*') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($this->fixtureDirectory);
        parent::tearDown();
    }

    public function test_explicit_translation_is_installed_once_and_update_flag_never_installs_new_one(): void
    {
        $csv = "Book,Chapter,Verse,Text\nGenesis,1,1,One\nGenesis,1,2,Two\n";
        $this->fakeScrollmapper($csv);

        $this->artisan('bible:import', ['--no-interaction' => true, '--update-translations' => true])->assertExitCode(0);
        $this->assertDatabaseCount('bibles', 0);
        Http::assertNothingSent();

        $this->artisan('bible:import', [
            '--no-interaction' => true,
            '--translation' => ['scrollmapper:Fixture', 'scrollmapper:Fixture'],
        ])->assertExitCode(0);
        $this->assertDatabaseCount('bible_contents', 2);
        $this->assertDatabaseHas('bible_data_imports', ['dataset_id' => 'scrollmapper:Fixture', 'row_count' => 2]);
        $first = DB::table('bible_data_imports')->where('dataset_id', 'scrollmapper:Fixture')->first();

        $this->artisan('bible:import', ['--no-interaction' => true, '--update-translations' => true])->assertExitCode(0);
        $this->assertDatabaseCount('bible_contents', 2);
        $this->assertSame($first->applied_hash, DB::table('bible_data_imports')->where('dataset_id', 'scrollmapper:Fixture')->value('applied_hash'));
    }

    public function test_corrupt_cross_reference_manifest_preserves_existing_rows(): void
    {
        $this->writeCrossReferenceFixture();
        Http::fake();
        $this->artisan('bible:import', ['--no-interaction' => true, '--cross-references' => true])->assertExitCode(0);
        $this->assertDatabaseCount('bibleverses_cross_ref', 1);

        file_put_contents($this->fixtureDirectory.'/'.OpenBibleData::PAYLOAD, "1001002\t0\t20008022\t0\n");
        $this->artisan('bible:import', ['--no-interaction' => true, '--update-cross-references' => true])->assertExitCode(1);
        $this->assertDatabaseHas('bibleverses_cross_ref', ['source' => 1001001, 'target_from' => 20008022]);
        $this->assertDatabaseCount('bibleverses_cross_ref', 1);
        Http::assertNothingSent();
    }

    public function test_database_insert_failure_rolls_back_previous_translation_and_version(): void
    {
        $csv = "Book,Chapter,Verse,Text\nGenesis,1,1,One\n";
        $this->fakeScrollmapper($csv);
        $arguments = ['--no-interaction' => true, '--translation' => ['scrollmapper:Fixture']];
        $this->artisan('bible:import', $arguments)->assertExitCode(0);
        $appliedHash = DB::table('bible_data_imports')->where('dataset_id', 'scrollmapper:Fixture')->value('applied_hash');

        $csv = "Book,Chapter,Verse,Text\nGenesis,1,1,Two\n";
        $failInsert = true;
        DB::connection()->beforeExecuting(function (string $query) use (&$failInsert): void {
            if ($failInsert && str_contains(strtolower($query), 'insert into `bible_contents`')) {
                throw new RuntimeException('Simulierter Datenbankfehler.');
            }
        });
        try {
            $this->artisan('bible:import', $arguments)->assertExitCode(1);
        } finally {
            $failInsert = false;
        }
        $this->assertDatabaseHas('bible_contents', ['verse' => 1001001, 'text' => 'One']);
        $this->assertDatabaseCount('bible_contents', 1);
        $this->assertSame($appliedHash, DB::table('bible_data_imports')->where('dataset_id', 'scrollmapper:Fixture')->value('applied_hash'));
    }

    public function test_all_selected_sources_are_checked_before_any_database_mutation(): void
    {
        $csv = "Book,Chapter,Verse,Text\nGenesis,1,1,One\n";
        $this->fakeScrollmapper($csv);
        $this->writeCrossReferenceFixture();
        file_put_contents($this->fixtureDirectory.'/'.OpenBibleData::PAYLOAD, 'corrupt');

        $this->artisan('bible:import', [
            '--no-interaction' => true,
            '--translation' => ['scrollmapper:Fixture'],
            '--cross-references' => true,
        ])->assertExitCode(1);
        $this->assertDatabaseCount('bibles', 0);
        $this->assertDatabaseCount('bible_contents', 0);
        $this->assertDatabaseCount('bible_data_imports', 0);
    }

    public function test_changed_rights_update_attribution_without_rewriting_verses(): void
    {
        $csv = "Book,Chapter,Verse,Text\nGenesis,1,1,One\n";
        $this->fakeScrollmapper($csv);
        $arguments = ['--no-interaction' => true, '--translation' => ['scrollmapper:Fixture']];
        $this->artisan('bible:import', $arguments)->assertExitCode(0);
        $hash = DB::table('bible_data_imports')->where('dataset_id', 'scrollmapper:Fixture')->value('applied_hash');

        $this->fakeLicense = 'CC BY';
        $this->artisan('bible:import', ['--no-interaction' => true, '--update-translations' => true])->assertExitCode(0);
        $this->assertDatabaseHas('bibles', ['uuid' => 'scrollmapper:Fixture', 'rights' => 'CC BY']);
        $this->assertDatabaseHas('bible_contents', ['verse' => 1001001, 'text' => 'One']);
        $this->assertSame($hash, DB::table('bible_data_imports')->where('dataset_id', 'scrollmapper:Fixture')->value('applied_hash'));
    }

    public function test_ambiguous_unmanaged_cross_reference_rows_are_not_deleted(): void
    {
        $this->writeCrossReferenceFixture();
        DB::table('bibleverses_cross_ref')->insert([
            'source' => 1001002, 'relevance' => 3, 'target_from' => 20008022, 'target_to' => 0,
        ]);

        $this->artisan('bible:import', ['--no-interaction' => true, '--cross-references' => true])->assertExitCode(1);
        $this->assertDatabaseHas('bibleverses_cross_ref', ['source' => 1001002, 'relevance' => 3]);
        $this->assertDatabaseCount('bibleverses_cross_ref', 1);
        $this->assertDatabaseCount('bible_data_imports', 0);
    }

    private function fakeScrollmapper(string &$csv): void
    {
        $revision = str_repeat('a', 40);
        Http::fake(function (Request $request) use ($revision, &$csv) {
            $url = $request->url();
            if (str_ends_with($url, '/commits/master')) {
                return Http::response(['sha' => $revision]);
            }
            if (str_contains($url, '/git/trees/')) {
                $blob = sha1('blob '.strlen($csv)."\0".$csv);
                return Http::response(['truncated' => false, 'tree' => [
                    ['type' => 'blob', 'path' => 'formats/csv/Fixture.csv', 'sha' => $blob, 'size' => strlen($csv)],
                    ['type' => 'blob', 'path' => 'sources/en/Fixture/README.md', 'sha' => str_repeat('b', 40)],
                ]]);
            }
            if (str_ends_with($url, '/docs/main_readme/translation_list.md')) {
                return Http::response("## Available Translations (1)\n- **Fixture (en)**: Fixture Bible\n");
            }
            if (str_ends_with($url, '/sources/en/Fixture/README.md')) {
                return Http::response("# Fixture Bible\n**License:** {$this->fakeLicense}\n");
            }
            if (str_ends_with($url, '/formats/csv/Fixture.csv')) {
                return Http::response($csv);
            }

            return Http::response('unexpected request', 404);
        });
    }

    private function writeCrossReferenceFixture(): void
    {
        $payload = "1001001\t0\t20008022\t0\n";
        file_put_contents($this->fixtureDirectory.'/'.OpenBibleData::PAYLOAD, $payload);
        file_put_contents($this->fixtureDirectory.'/'.OpenBibleData::MANIFEST, json_encode([
            'schema' => OpenBibleData::VERSION, 'dataset_id' => OpenBibleData::ID,
            'source_url' => config('bible_data.openbible_url'), 'source_date' => '2026-09-28',
            'rights' => 'CC BY', 'payload' => OpenBibleData::PAYLOAD,
            'sha256' => hash('sha256', $payload), 'row_count' => 1,
        ], JSON_THROW_ON_ERROR));
    }
}
