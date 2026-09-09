<?php

namespace Tests\Feature\UpgradeBaseline;

use App\Models\ImageFile;
use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Text;
use App\Models\User;
use App\ResourceLimitations\PageLimitation;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\ResourceHandling\TextHandlingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SerializationAndStorageContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_resource_path_columns_keep_their_nullable_schema(): void
    {
        $columns = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', config('database.connections.mysql.database'))
            ->where('TABLE_NAME', 'resources')
            ->whereIn('COLUMN_NAME', ['remote_path', 'local_path'])
            ->get()
            ->keyBy('COLUMN_NAME');

        $this->assertSame('YES', $columns['remote_path']->IS_NULLABLE);
        $this->assertSame(255, $columns['remote_path']->CHARACTER_MAXIMUM_LENGTH);
        $this->assertSame('YES', $columns['local_path']->IS_NULLABLE);
        $this->assertSame(512, $columns['local_path']->CHARACTER_MAXIMUM_LENGTH);
    }

    public function test_material_resource_limitation_keeps_its_php_serialized_object_contract(): void
    {
        $user = User::factory()->create();
        $material = $this->material($user);
        $pdf = $this->resource(PdfFile::class, $user);
        $limitation = (new PageLimitation())->setPages([7, 2, 3, 2]);

        $material->resources()->attach($pdf->id, ['limitation' => $limitation]);

        $raw = DB::table('material_resource')
            ->where('material_id', $material->id)
            ->where('resource_id', $pdf->id)
            ->value('limitation');
        $pivot = $material->fresh()->resources()->firstOrFail()->pivot;

        $this->assertStringStartsWith('O:' . strlen(PageLimitation::class) . ':"' . PageLimitation::class . '"', $raw);
        $this->assertInstanceOf(PageLimitation::class, $pivot->limitation);
        $this->assertSame([2, 2, 3, 7], $pivot->limitation->getPages());
        $this->assertSame(['pages' => [2, 2, 3, 7]], $pivot->limitation->toArray());
    }

    public function test_null_limitation_round_trips_through_its_serialized_null_value(): void
    {
        $user = User::factory()->create();
        $material = $this->material($user);
        $pdf = $this->resource(PdfFile::class, $user);

        $material->resources()->attach($pdf->id, ['limitation' => null]);

        $raw = DB::table('material_resource')->value('limitation');
        $pivot = $material->fresh()->resources()->firstOrFail()->pivot;

        $this->assertSame('N;', $raw);
        $this->assertNull($pivot->limitation);
    }

    public function test_disk_qualified_local_paths_round_trip_and_invalidate_the_file_cache(): void
    {
        $user = User::factory()->create();
        /** @var ImageFile $file */
        $file = $this->resource(ImageFile::class, $user);
        $file->setLocalStorageAndPath('resources', 'first/image.jpg');

        $this->assertSame(['resources', 'first/image.jpg'], $file->getLocalStorageAndPath());
        $file->setLocalStorageAndPath('archive', 'second/image.jpg');
        $file->save();

        $this->assertSame(['archive', 'second/image.jpg'], $file->getLocalStorageAndPath());
        $this->assertSame('archive::second/image.jpg', $file->fresh()->getRawOriginal('local_path'));
    }

    public function test_file_archiving_copies_bytes_to_the_fake_archive_without_removing_the_source(): void
    {
        $user = User::factory()->create();
        /** @var ImageFile $file */
        $file = $this->resource(ImageFile::class, $user);
        $file->original_filename = 'original.jpg';
        $file->setLocalStorageAndPath('resources', 'user/image.jpg');
        $file->save();
        Storage::disk('resources')->put('user/image.jpg', 'binary-image-contract');
        $expectedArchivePath = strftime('%G/%m/%d/') . $file->id . '.backup_original.jpg';
        Storage::disk('archive')->delete($expectedArchivePath);

        [$archive, $archivePath] = resolve(FileHandlingService::class)->archiveResource($file);

        $this->assertSame('binary-image-contract', $archive->get($archivePath));
        Storage::disk('resources')->assertExists('user/image.jpg');
        Storage::disk('archive')->assertExists($archivePath);
        $this->assertStringEndsWith('/' . $file->id . '.backup_original.jpg', $archivePath);
    }

    public function test_text_archiving_preserves_content_and_uses_the_established_backup_suffix(): void
    {
        $user = User::factory()->create();
        /** @var Text $text */
        $text = $this->resource(Text::class, $user);
        $text->content = 'Inhalt des Textvertrags';
        $text->save();
        $expectedArchivePath = strftime('%G/%m/%d/') . $text->id . '.backup_txt';
        Storage::disk('archive')->delete($expectedArchivePath);

        [$archive, $archivePath] = resolve(TextHandlingService::class)->archiveResource($text);

        $this->assertSame('Inhalt des Textvertrags', $archive->get($archivePath));
        $this->assertStringEndsWith('/' . $text->id . '.backup_txt', $archivePath);
    }

    public function test_deleting_a_local_file_removes_only_the_fake_resource_file_and_clears_model_metadata(): void
    {
        $user = User::factory()->create();
        /** @var ImageFile $file */
        $file = $this->resource(ImageFile::class, $user);
        $file->original_filename = 'delete.jpg';
        $file->setLocalStorageAndPath('resources', 'user/delete.jpg');
        Storage::disk('resources')->put('user/delete.jpg', 'delete-me');

        $this->assertTrue($file->deleteLocalFile());

        Storage::disk('resources')->assertMissing('user/delete.jpg');
        $this->assertNull($file->getRawOriginal('local_path'));
        $this->assertSame('', $file->original_filename);
    }

    private function material(User $user): Material
    {
        $material = new Material(['title' => 'Contract material']);
        $material->created_by = $user->id;
        $material->modified_by = $user->id;
        $material->save();

        return $material;
    }

    private function resource(string $class, User $user)
    {
        $resource = new $class();
        $resource->created_by = $user->id;
        $resource->save();

        return $resource;
    }
}
