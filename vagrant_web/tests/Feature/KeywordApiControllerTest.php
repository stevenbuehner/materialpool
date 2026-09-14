<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KeywordApiControllerTest extends TestCase {

	use RefreshDatabase;

	protected function setUp(): void {
		parent::setUp();

		$user = User::factory()->create();
		$manager = Role::create(['name' => 'Tag-Verwaltung Test', 'guard_name' => 'web']);
		$manager->givePermissionTo(SystemPermissions::KEYWORDS_MANAGE);
		$user->assignRole($manager);
		Passport::actingAs($user);
	}

	public function testKeywordCreatePersonWithType() {

		$method = 'post';
		$uri    = route('api.v1.keywords.create');
		$data   = [
			'type'  => 'person',
			'title' => 'My Name'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "My Name", 'person', 'person_my_name', NULL);
	}

	protected function assertKeywordStructure(TestResponse $response) {
		$response->assertJsonStructure([
										   'id', 'title', 'type', 'lc_title', 'parent_id',  'custom_icon'
									   ]);
	}

	protected function assertKeywordData(TestResponse $response, $title, $type, $lc_title, $parent_id = NULL) {
		$response->assertJson([
								  'title'     => $title,
								  'type'      => $type,
								  'lc_title'  => $lc_title,
								  'parent_id' => $parent_id
							  ]);
	}

	public function testKeywordCreateKeywordWithType() {

		$method = 'post';
		$uri    = route('api.v1.keywords.create');
		$data   = [
			'type'  => 'key',
			'title' => 'My Test'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "My Test", 'key', 'key_my_test', NULL);
	}

	public function testKeywordCreateKeywordWithoutType() {

		$method = 'post';
		$uri    = route('api.v1.keywords.create');
		$data   = [
			'title' => 'Some Keyword'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "Some Keyword", 'key', 'key_some_keyword', NULL);
	}

	public function testKeywordCreatePrefixedTitleWithoutType() {

		$method = 'post';
		$uri    = route('api.v1.keywords.create');
		$data   = [
			'title' => 'Person: Some Person'
		];

		$response = $this->json($method, $uri, $data);

		$response->assertStatus(200);
		$this->assertKeywordStructure($response);
		$this->assertKeywordData($response, "Person: Some Person", 'key', 'key_person_some_person', NULL);
	}

	public function testKeywordIndexUsesNestedSetPreorder() {
		$root = $this->createRoot('Root');
		$first = $this->createChild('First', $root);
		$this->createChild('Leaf', $first);
		$this->createChild('Second', $root);
		$this->createRoot('Other root');

		$response = $this->getJson(route('api.v1.keywords.index'));

		$response->assertOk();
		$this->assertSame(
			['Deutsch', 'Englisch', 'Französisch', 'Root', 'First', 'Leaf', 'Second', 'Other root'],
			array_column($response->json('data'), 'title')
		);
	}

	public function testKeywordShowExposesOrderedAncestorsAndDescendantsWithoutBoundaries() {
		$root = $this->createRoot('Root');
		$branch = $this->createChild('Branch', $root);
		$leaf = $this->createChild('Leaf', $branch);
		$this->createChild('Sibling', $root);

		$response = $this->getJson(route('api.v1.keywords.show', $branch));

		$response->assertOk()->assertJsonPath('id', $branch->id);
		$this->assertSame([$root->id], array_column($response->json('ancestors'), 'id'));
		$this->assertSame([$leaf->id], array_column($response->json('descendants'), 'id'));
		$this->assertArrayNotHasKey('_lft', $response->json());
		$this->assertArrayNotHasKey('_rgt', $response->json());
	}

	public function testKeywordUpdateMovesACompleteSubtreeToTheRequestedParent() {
		$firstRoot = $this->createRoot('First root');
		$secondRoot = $this->createRoot('Second root');
		$branch = $this->createChild('Branch', $firstRoot);
		$leaf = $this->createChild('Leaf', $branch);

		$response = $this->putJson(route('api.v1.keywords.update', $branch), [
			'parent_id' => $secondRoot->id,
		]);

		$response->assertOk()->assertJsonPath('parent_id', $secondRoot->id);
		$this->assertSame([], $firstRoot->fresh()->descendants()->pluck('id')->all());
		$this->assertSame(
			[$branch->id, $leaf->id],
			$secondRoot->fresh()->descendants()->defaultOrder()->pluck('id')->all()
		);
		$this->assertFalse(Keyword::query()->isBroken());
	}

	public function testKeywordUpdateWithNullParentPromotesTheCompleteSubtreeToARoot() {
		$root = $this->createRoot('Root');
		$branch = $this->createChild('Branch', $root);
		$leaf = $this->createChild('Leaf', $branch);

		$response = $this->putJson(route('api.v1.keywords.update', $branch), [
			'parent_id' => null,
		]);

		$response->assertOk()->assertJsonPath('parent_id', null);
		$this->assertSame([], $root->fresh()->descendants()->pluck('id')->all());
		$this->assertSame([$leaf->id], $branch->fresh()->descendants()->pluck('id')->all());
		$this->assertFalse(Keyword::query()->isBroken());
	}

	public function testRelationsCountDistinguishesDirectChildrenAndAllDescendants() {
		$root = $this->createRoot('Root');
		$branch = $this->createChild('Branch', $root);
		$this->createChild('Leaf', $branch);
		$this->createChild('Second', $root);

		$response = $this->getJson(route('api.v1.keywords.relations_count', $root));

		$response->assertOk()->assertExactJson([
			'materials_count' => 0,
			'material_authors_count' => 0,
			'children_count' => 2,
			'descendants_count' => 3,
		]);
	}

	private function createRoot(string $title, string $type = 'key'): Keyword {
		$keyword = new Keyword(['title' => $title, 'type' => $type]);
		$keyword->saveAsRoot();

		return $keyword->fresh();
	}

	private function createChild(string $title, Keyword $parent, string $type = 'key'): Keyword {
		$keyword = new Keyword(['title' => $title, 'type' => $type]);
		$keyword->appendToNode($parent)->save();

		return $keyword->fresh();
	}

}
