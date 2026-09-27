<?php

namespace Tests\Feature\UpgradeBaseline;

use App\Models\AudioFile;
use App\Models\Book;
use App\Models\DocumentFile;
use App\Models\File;
use App\Models\ImageFile;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
use App\Models\Url;
use App\Models\User;
use App\Models\VideoFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ResourceStiContractTest extends TestCase
{
    use RefreshDatabase;

    private const TYPE_MAP = [
        'res' => Resource::class,
        'link' => Url::class,
        'file' => File::class,
        'audio' => AudioFile::class,
        'video' => VideoFile::class,
        'image' => ImageFile::class,
        'doc' => DocumentFile::class,
        'pdf' => PdfFile::class,
        'text' => Text::class,
        'book' => Book::class,
    ];

    public function test_the_complete_persisted_type_map_is_stable(): void
    {
        $this->assertSame(self::TYPE_MAP, Resource::getSingleTableTypeMap());
        $this->assertSame(
            ['res', 'link', 'file', 'text', 'book', 'audio', 'video', 'image', 'doc'],
            Resource::$allResourceTypeKeys
        );
        $this->assertSame(PdfFile::class, Resource::getSingleTableClass('pdf'));
        $this->assertNull(Resource::getSingleTableClass('unknown'));
    }

    public function test_each_model_class_writes_its_established_type_discriminator(): void
    {
        $user = User::factory()->create();

        foreach (self::TYPE_MAP as $type => $class) {
            /** @var Resource $resource */
            $resource = new $class();
            $resource->created_by = $user->id;
            $resource->save();

            $this->assertSame($type, DB::table('resources')->where('id', $resource->id)->value('type'));
            $this->assertSame($type, $resource->type);
        }
    }

    public function test_base_queries_hydrate_every_stored_type_as_the_expected_concrete_class(): void
    {
        $user = User::factory()->create();
        $ids = [];

        foreach (self::TYPE_MAP as $type => $class) {
            $ids[$type] = DB::table('resources')->insertGetId([
                'created_by' => $user->id,
                'notes' => $type,
                'options' => '[]',
                'is_public' => false,
                'type' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (self::TYPE_MAP as $type => $class) {
            $resource = Resource::query()->findOrFail($ids[$type]);
            $this->assertSame($class, get_class($resource), "Stored type {$type} hydrated incorrectly.");
            $this->assertSame($type, $resource->type);
        }
    }

    public function test_file_queries_include_all_file_subtypes_but_not_other_resource_types(): void
    {
        $user = User::factory()->create();

        foreach (self::TYPE_MAP as $class) {
            /** @var Resource $resource */
            $resource = new $class();
            $resource->created_by = $user->id;
            $resource->save();
        }

        $this->assertSame(
            ['file', 'audio', 'video', 'image', 'doc', 'pdf'],
            File::query()->orderBy('id')->get()->pluck('type')->all()
        );
        $this->assertSame(['res'], Resource::query()->where('type', 'res')->pluck('type')->all());
    }

    public function test_options_round_trip_while_internal_storage_fields_stay_hidden_from_json(): void
    {
        $user = User::factory()->create();
        $text = new Text(['notes' => 'sichtbar']);
        $text->content = '  Vertragstext  ';
        $text->original_filename = 'quelle.txt';
        $text->created_by = $user->id;
        $text->setAttribute('local_path', 'resources::42/text.txt');
        $text->save();

        $fresh = Resource::query()->findOrFail($text->id);
        $serialized = $fresh->toArray();

        $this->assertInstanceOf(Text::class, $fresh);
        $this->assertSame('Vertragstext', $fresh->content);
        $this->assertSame('quelle.txt', $fresh->original_filename);
        $this->assertSame('resources::42/text.txt', $fresh->getRawOriginal('local_path'));
        $this->assertArrayNotHasKey('options', $serialized);
        $this->assertArrayNotHasKey('local_path', $serialized);
        $this->assertSame('Vertragstext', $serialized['content']);
        $this->assertSame('quelle.txt', $serialized['original_filename']);
    }

    public function test_dynamic_text_attributes_are_not_mass_assigned_through_the_constructor(): void
    {
        $text = new Text([
            'content' => 'constructor content',
            'original_filename' => 'constructor.txt',
        ]);

        $this->assertNull($text->content);
        $this->assertNull($text->original_filename);

        $text->content = 'assigned afterwards';
        $text->original_filename = 'afterwards.txt';

        $this->assertSame('assigned afterwards', $text->content);
        $this->assertSame('afterwards.txt', $text->original_filename);
    }

    public function test_image_validation_keeps_accepting_svg_uploads(): void
    {
        $svg = UploadedFile::fake()->createWithContent(
            'vector.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><path d="M0 0h1v1H0z"/></svg>'
        );

        $validator = Validator::make(['file' => $svg], ImageFile::getValidationRules());

        $this->assertTrue($validator->passes(), $validator->errors()->first('file'));
    }
}
