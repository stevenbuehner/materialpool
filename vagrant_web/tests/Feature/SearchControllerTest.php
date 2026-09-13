<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Keyword;
use App\Models\User;
use App\Models\VideoFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_suggestions_accept_an_empty_query_parameter(): void
    {
        $this->withoutDeprecationHandling();

        $user = User::factory()->create();
        $keyword = Keyword::factory()->create(['type' => 'person']);

        $response = $this->actingAs($user)->getJson(route('searchbar.guessKeywords', [
            'q' => '',
            'limit' => 20,
            'page' => 1,
            't' => 'person',
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $keyword->id);
    }

    public function test_search_endpoints_accept_empty_query_parameters(): void
    {
        $this->withoutDeprecationHandling();

        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('searchbar.guess', ['q' => '']))->assertOk();
        $this->actingAs($user)->postJson(route('searchbar.guessBibleverses'), ['q' => ''])->assertOk();
        $this->actingAs($user)->postJson(route('pool.searchbar.get'), ['q' => ''])->assertOk();
    }

    public function test_search_serializes_a_material_with_a_missing_local_file(): void
    {
        $user = User::factory()->create();
        $material = Material::factory()->create();
        $video = new VideoFile();
        $video->setAttribute('created_by', $user->id);
        $video->setAttribute('local_path', config('app.disks.resources').'::missing.mp4');
        $video->save();
        $material->resources()->attach($video);

        $response = $this->actingAs($user)->postJson(route('pool.searchbar.get'), [
            'q' => [],
            'per_page' => 30,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $material->id);
        $response->assertJsonPath('data.0.resources.0.filesize', null);
        $response->assertJsonPath('data.0.resources.0.mime_type', '');
    }
}
