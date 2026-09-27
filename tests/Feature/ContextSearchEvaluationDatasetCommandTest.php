<?php

namespace Tests\Feature;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\ContextSearchEvaluationDatasetMember;
use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Text;
use App\Models\User;
use App\Services\ContextSearch\EvaluationDatasetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

final class ContextSearchEvaluationDatasetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_text_is_only_frozen_with_explicit_reason_and_exported_with_hashes(): void
    {
        Storage::fake('context_search_evaluation');
        $user = User::factory()->create();
        $material = Material::factory()->privatelyVisible()->create(['created_by' => $user->getKey(), 'modified_by' => $user->getKey()]);
        $text = Text::factory()->create(['created_by' => $user->getKey(), 'is_public' => false, 'content' => 'Ein eindeutiger vertraulicher Testtext.']);
        $material->resources()->attach($text->getKey(), ['limitation' => 'Nur für Testzwecke']);

        $this->artisan('context-search:dataset:freeze', [
            'purpose' => 'calibration', '--materials' => (string) $material->getKey(),
        ])->assertExitCode(1);

        $this->artisan('context-search:dataset:freeze', [
            'purpose' => 'calibration', '--materials' => (string) $material->getKey(),
            '--include-private' => true, '--reason' => 'Kuratiertes Testset',
        ])->expectsOutputToContain('eingefroren')->assertExitCode(0);

        $dataset = ContextSearchEvaluationDataset::query()->sole();
        $this->assertTrue($dataset->includes_private);
        $this->assertSame(1, $dataset->material_count);
        $this->assertSame(1, $dataset->resource_count);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $dataset->manifest_hash);

        $dataset->update(['version' => 4]);

        $this->artisan('context-search:dataset:export', ['dataset' => $dataset->getKey()])
            ->expectsOutputToContain('Archiv-Prüfsumme')
            ->assertExitCode(0);

        $exportProgress = [];
        app(EvaluationDatasetService::class)->export($dataset->fresh(), function (string $phase, int $current, int $total) use (&$exportProgress): void {
            $exportProgress[$phase] = [$current, $total];
        });
        $this->assertSame([2, 2], $exportProgress['Archiv vorbereiten']);
        $this->assertSame([1000, 1000], $exportProgress['Archiv schreiben']);
        $this->assertSame($exportProgress['Archiv-Prüfsumme berechnen'][1], $exportProgress['Archiv-Prüfsumme berechnen'][0]);

        $dataset->refresh();
        $this->assertSame(ContextSearchEvaluationDataset::STATUS_EXPORTED, $dataset->status);
        $this->assertSame('exports/calibration-v4-'.$dataset->getKey().'.zip', $dataset->archive_path);
        $this->assertTrue(Storage::disk('context_search_evaluation')->exists($dataset->archive_path));

        $this->artisan('context-search:dataset:verify', ['archive' => $dataset->archive_path])
            ->expectsOutputToContain('Manifest-Prüfsumme')
            ->assertExitCode(0);

        $progress = [];
        $verified = app(EvaluationDatasetService::class)->verify($dataset->archive_path, function (string $phase, int $current, int $total) use (&$progress): void {
            $progress[$phase] = [$current, $total];
        });
        $this->assertSame($dataset->archive_hash, $verified['archive_hash']);
        $this->assertSame([1, 1], $progress['Archiveinträge prüfen']);
        $this->assertSame($progress['Archiv-Prüfsumme berechnen'][1], $progress['Archiv-Prüfsumme berechnen'][0]);
    }

    public function test_export_without_uuid_lists_frozen_datasets_for_interactive_selection(): void
    {
        Storage::fake('context_search_evaluation');
        $user = User::factory()->create();
        $material = Material::factory()->publiclyVisible()->create(['created_by' => $user->id, 'modified_by' => $user->id]);
        $text = Text::factory()->create(['created_by' => $user->id, 'is_public' => true, 'content' => 'Synthetischer Exporttext.']);
        $material->resources()->attach($text->id);
        $frozen = app(EvaluationDatasetService::class)->freeze('ocr', [$material->id], [], false, null);
        $draft = ContextSearchEvaluationDataset::query()->create([
            'purpose' => 'load', 'status' => ContextSearchEvaluationDataset::STATUS_DRAFT,
            'manifest' => [], 'manifest_hash' => str_repeat('0', 64),
        ]);

        $choice = $frozen->id.' (ocr)';
        $this->artisan('context-search:dataset:export', ['--no-interaction' => true])
            ->expectsOutputToContain($frozen->id)
            ->assertExitCode(1);
        $this->assertSame(ContextSearchEvaluationDataset::STATUS_FROZEN, $frozen->fresh()->status);

        $this->artisan('context-search:dataset:export')
            ->expectsTable(
                ['UUID', 'Zweck', 'Materialien', 'Ressourcen'],
                [[$frozen->id, 'ocr', 1, 1]],
            )
            ->expectsChoice('Welchen Datensatz exportieren?', $choice, [$choice, 'Abbrechen'])
            ->expectsOutputToContain('Evaluationsarchiv erstellt')
            ->assertExitCode(0);

        $this->assertSame(ContextSearchEvaluationDataset::STATUS_EXPORTED, $frozen->fresh()->status);
        $this->assertSame('exports/ocr-v1-'.$frozen->getKey().'.zip', $frozen->fresh()->archive_path);
        $this->assertSame(ContextSearchEvaluationDataset::STATUS_DRAFT, $draft->fresh()->status);
    }

    public function test_imports_a_synthetic_verified_text_and_pdf_dataset_only_when_explicitly_enabled(): void
    {
        Storage::fake('context_search_evaluation');
        Storage::fake('resources');
        $datasetId = (string) Str::uuid();
        $pdfContent = "%PDF-1.4\nSynthetischer PDF-Testinhalt\n%%EOF\n";
        $manifest = [
            'dataset_id' => $datasetId,
            'format' => 'materialpool-context-search-evaluation-v1',
            'frozen_at' => now()->toAtomString(),
            'includes_private' => false,
            'materials' => [[
                'source_id' => 7001, 'title' => 'Synthetisches Material', 'description' => 'Nur Testdaten.', 'rating' => 1,
                'from_bot' => false, 'is_public' => false, 'flag' => null, 'icon_of_bundle' => null,
                'created_at' => null, 'updated_at' => null, 'keywords' => [], 'bibleverses' => [],
            ]],
            'private_reason' => null,
            'purpose' => 'acceptance',
            'resources' => [
                [
                    'source_id' => 8001, 'type' => 'text', 'is_public' => false, 'notes' => '',
                    'revision_hash' => hash('sha256', 'Synthetischer Importtext.'), 'content_hash' => null, 'filesize' => 27,
                    'created_at' => null, 'updated_at' => null, 'archive_path' => 'texts/8001.txt',
                    'original_filename' => null, 'text' => 'Synthetischer Importtext.', 'material_ids' => [7001],
                ],
                [
                    'source_id' => 8002, 'type' => 'pdf', 'is_public' => false, 'notes' => '',
                    'revision_hash' => hash('sha256', $pdfContent), 'content_hash' => null, 'filesize' => strlen($pdfContent),
                    'created_at' => null, 'updated_at' => null, 'archive_path' => 'files/8002.pdf',
                    'original_filename' => 'synthetic.pdf', 'text' => null, 'material_ids' => [7001],
                ],
            ],
        ];
        $manifest['manifest_hash'] = hash('sha256', json_encode($this->canonicalize($manifest), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        Storage::disk('context_search_evaluation')->makeDirectory('incoming');
        $archive = Storage::disk('context_search_evaluation')->path('incoming/'.$datasetId.'.zip');
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('manifest.json', json_encode($this->canonicalize($manifest), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('texts/8001.txt', 'Synthetischer Importtext.');
        $zip->addFromString('files/8002.pdf', $pdfContent);
        $zip->close();

        $this->artisan('context-search:dataset:import', ['archive' => 'incoming/'.$datasetId.'.zip'])
            ->expectsOutputToContain('context_search.evaluation.import_enabled ist deaktiviert')
            ->expectsOutputToContain('CONTEXT_SEARCH_EVALUATION_IMPORT_ENABLED=true')
            ->expectsOutputToContain('artisan config:clear')
            ->assertExitCode(1);
        config()->set('context_search.evaluation.import_enabled', true);

        $materialsBefore = Material::query()->count();
        $resourcesBefore = Text::query()->count();
        $pdfsBefore = PdfFile::query()->count();
        $this->artisan('context-search:dataset:import', ['archive' => 'incoming/'.$datasetId.'.zip'])
            ->expectsOutputToContain('isoliert importiert')
            ->assertExitCode(0);
        $this->assertSame($materialsBefore + 1, Material::query()->count());
        $this->assertSame($resourcesBefore + 1, Text::query()->count());
        $this->assertSame($pdfsBefore + 1, PdfFile::query()->count());
        $imported = ContextSearchEvaluationDataset::query()->findOrFail($datasetId);
        $this->assertSame(ContextSearchEvaluationDataset::STATUS_FROZEN, $imported->status);
        $this->assertSame(3, $imported->members()->count());
        $hashes = $imported->members()->where('member_type', ContextSearchEvaluationDatasetMember::TYPE_RESOURCE)->pluck('source_revision_hash')->all();
        $this->assertContains(hash('sha256', 'Synthetischer Importtext.'), $hashes);
        $this->assertContains(hash('sha256', $pdfContent), $hashes);

        $this->artisan('context-search:dataset:import', ['archive' => 'incoming/'.$datasetId.'.zip'])->assertExitCode(0);
        $this->assertSame($materialsBefore + 1, Material::query()->count());
    }

    public function test_import_command_explains_why_production_is_blocked_even_with_import_enabled(): void
    {
        config()->set('context_search.evaluation.import_enabled', true);
        $this->app->instance('env', 'production');

        $this->artisan('context-search:dataset:import', ['archive' => 'incoming/example.zip'])
            ->expectsOutputToContain('APP_ENV=production ist gesperrt')
            ->expectsOutputToContain('getrennten Evaluationsumgebung')
            ->assertExitCode(1);
    }

    public function test_inventory_reports_only_counts_for_pdf_and_text_sources(): void
    {
        $user = User::factory()->create();
        $material = Material::factory()->publiclyVisible()->create(['created_by' => $user->getKey(), 'modified_by' => $user->getKey()]);
        $text = Text::factory()->create(['created_by' => $user->getKey(), 'is_public' => true]);
        $material->resources()->attach($text->getKey());

        $this->artisan('context-search:dataset:inventory', ['--json' => true])
            ->expectsOutputToContain('"materials"')
            ->assertExitCode(0);
    }

    private function canonicalize(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->canonicalize($item);
            }
        }
        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
