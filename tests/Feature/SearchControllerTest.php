<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Keyword;
use App\Models\Resource;
use App\Models\User;
use App\Models\VideoFile;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_keyword_suggestions_accept_an_empty_query_parameter(): void
    {
        $this->withoutDeprecationHandling();

        $user = User::factory()->create();
        $keyword = Keyword::factory()->create(['type' => 'person']);

        $response = $this->actingAs($user)->getJson(route('pool.searchbar.guessKeywords', [
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

        $this->actingAs($user)->getJson(route('pool.searchbar.guess', ['q' => '']))->assertOk();
        $this->actingAs($user)->postJson(route('pool.searchbar.guessBibleverses'), ['q' => ''])->assertOk();
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

    public function test_search_can_order_materials_by_creation_or_modification_date(): void
    {
        $user = User::factory()->create();
        $older = Material::factory()->create([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDay(),
        ]);
        $newer = Material::factory()->create([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDays(2),
        ]);

        $this->actingAs($user)->postJson(route('pool.searchbar.get'), [
            'q' => [],
            'order_by' => 'created_at',
        ])->assertOk()->assertJsonPath('data.0.id', $newer->id);

        $this->actingAs($user)->postJson(route('pool.searchbar.get'), [
            'q' => [],
            'order_by' => 'updated_at',
        ])->assertOk()->assertJsonPath('data.0.id', $older->id);
    }

    public function test_search_does_not_return_a_foreign_material_without_the_public_material_permission(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $viewer->syncRoles([]);
        $material = Material::factory()->create([
            'created_by' => $owner->id,
            'modified_by' => $owner->id,
            'title' => 'Nur mit Public-Material-Recht sichtbar',
        ]);

        $response = $this->actingAs($viewer)->postJson(route('pool.searchbar.get'), [
            'q' => [[['type' => '*', 'text' => $material->title]]],
        ]);

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('total', 0);
    }

    public function test_search_with_public_material_permission_returns_only_public_and_own_materials(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $viewer->syncRoles([]);
        $role = Role::create(['name' => 'Nur öffentliche Materialien lesen', 'guard_name' => 'web']);
        $role->givePermissionTo(SystemPermissions::MATERIALS_VIEW_PUBLIC);
        $viewer->assignRole($role);
        $ownPrivate = Material::factory()->privatelyVisible()->create(['created_by' => $viewer->id, 'modified_by' => $viewer->id]);
        $foreignPublic = Material::factory()->publiclyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
        $foreignPrivate = Material::factory()->privatelyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);

        $response = $this->actingAs($viewer)->postJson(route('pool.searchbar.get'), ['q' => []]);

        $response->assertOk()->assertJsonPath('total', 2);
        $visibleIds = collect($response->json('data'))->pluck('id')->sort()->values()->all();
        $expectedIds = collect([$ownPrivate->id, $foreignPublic->id])->sort()->values()->all();
        $this->assertSame($expectedIds, $visibleIds);
        $this->assertNotContains($foreignPrivate->id, $visibleIds);
    }

    public function test_search_pagination_total_does_not_count_private_foreign_materials(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $viewer->syncRoles([]);
        $role = Role::create(['name' => 'Öffentliche Materialsuche', 'guard_name' => 'web']);
        $role->givePermissionTo(SystemPermissions::MATERIALS_VIEW_PUBLIC);
        $viewer->assignRole($role);
        Material::factory()->count(2)->publiclyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);
        Material::factory()->privatelyVisible()->create(['created_by' => $owner->id, 'modified_by' => $owner->id]);

        $response = $this->actingAs($viewer)->postJson(route('pool.searchbar.get'), [
            'q' => [],
            'per_page' => 1,
            'page' => 2,
        ]);

        $response->assertOk()->assertJsonPath('total', 2)->assertJsonCount(1, 'data');
    }

    public function test_search_does_not_serialize_a_private_foreign_resource(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $material = Material::factory()->create([
            'created_by' => $owner->id,
            'modified_by' => $owner->id,
        ]);
        $resource = Resource::factory()->create([
            'created_by' => $owner->id,
            'is_public' => false,
        ]);
        $material->resources()->attach($resource);

        $response = $this->actingAs($viewer)->postJson(route('pool.searchbar.get'), [
            'q' => [],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $material->id);
        $response->assertJsonPath('data.0.resources', []);
    }

    public function test_resource_type_search_does_not_match_a_private_foreign_resource(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $material = Material::factory()->create([
            'created_by' => $owner->id,
            'modified_by' => $owner->id,
        ]);
        $resource = Resource::factory()->create([
            'created_by' => $owner->id,
            'is_public' => false,
        ]);
        $material->resources()->attach($resource);

        $response = $this->actingAs($viewer)->postJson(route('pool.searchbar.get'), [
            'q' => [[['type' => 't', 'text' => 'res']]],
        ]);

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('total', 0);
    }

    public function test_search_rejects_an_unknown_sort_column(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('pool.searchbar.get'), [
            'q' => [],
            'order_by' => 'created_at; drop table materials',
        ])->assertUnprocessable()->assertJsonValidationErrors(['order_by']);
    }
}
