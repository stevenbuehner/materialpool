<?php

namespace Tests\Feature;

use App\Models\ContextSearchEvaluationDataset;
use App\Models\Material;
use App\Models\Text;
use App\Models\User;
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

        $this->artisan('context-search:dataset:export', ['dataset' => $dataset->getKey()])
            ->expectsOutputToContain('Archiv-Prüfsumme')
            ->assertExitCode(0);

        $dataset->refresh();
        $this->assertSame(ContextSearchEvaluationDataset::STATUS_EXPORTED, $dataset->status);
        $this->assertTrue(Storage::disk('context_search_evaluation')->exists($dataset->archive_path));

        $this->artisan('context-search:dataset:verify', ['archive' => $dataset->archive_path])
            ->expectsOutputToContain('Manifest-Prüfsumme')
            ->assertExitCode(0);

    }

    public function test_imports_a_synthetic_verified_text_dataset_only_when_explicitly_enabled(): void
    {
        Storage::fake('context_search_evaluation');
        $datasetId = (string) Str::uuid();
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
            'resources' => [[
                'source_id' => 8001, 'type' => 'text', 'is_public' => false, 'notes' => '',
                'revision_hash' => hash('sha256', 'Synthetischer Importtext.'), 'content_hash' => null, 'filesize' => 27,
                'created_at' => null, 'updated_at' => null, 'archive_path' => 'texts/8001.txt',
                'original_filename' => null, 'text' => 'Synthetischer Importtext.', 'material_ids' => [7001],
            ]],
        ];
        $manifest['manifest_hash'] = hash('sha256', json_encode($this->canonicalize($manifest), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        Storage::disk('context_search_evaluation')->makeDirectory('incoming');
        $archive = Storage::disk('context_search_evaluation')->path('incoming/'.$datasetId.'.zip');
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('manifest.json', json_encode($this->canonicalize($manifest), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('texts/8001.txt', 'Synthetischer Importtext.');
        $zip->close();

        $this->artisan('context-search:dataset:import', ['archive' => 'incoming/'.$datasetId.'.zip'])->assertExitCode(1);
        config()->set('context_search.evaluation.import_enabled', true);

        $materialsBefore = Material::query()->count();
        $resourcesBefore = Text::query()->count();
        $this->artisan('context-search:dataset:import', ['archive' => 'incoming/'.$datasetId.'.zip'])
            ->expectsOutputToContain('isoliert importiert')
            ->assertExitCode(0);
        $this->assertSame($materialsBefore + 1, Material::query()->count());
        $this->assertSame($resourcesBefore + 1, Text::query()->count());

        $this->artisan('context-search:dataset:import', ['archive' => 'incoming/'.$datasetId.'.zip'])->assertExitCode(0);
        $this->assertSame($materialsBefore + 1, Material::query()->count());
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
