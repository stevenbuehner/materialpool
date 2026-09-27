<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Models\Material;
use App\Models\User;
use App\Services\KeywordHandling\KeywordHandlingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class KeywordNestedSetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_forest_with_stable_preorder_boundaries(): void
    {
        $firstRoot = $this->root('Themen');
        $firstChild = $this->child('Glaube', $firstRoot);
        $grandchild = $this->child('Gebet', $firstChild);
        $secondChild = $this->child('Gemeinschaft', $firstRoot);
        $secondRoot = $this->root('Orte', 'place');

        $this->assertTreeRows([
            [1, null, 1, 2],
            [2, null, 3, 4],
            [3, null, 5, 6],
            [$firstRoot->id, null, 7, 14],
            [$firstChild->id, $firstRoot->id, 8, 11],
            [$grandchild->id, $firstChild->id, 9, 10],
            [$secondChild->id, $firstRoot->id, 12, 13],
            [$secondRoot->id, null, 15, 16],
        ]);
        $this->assertSame(
            ['Deutsch', 'Englisch', 'Französisch', 'Themen', 'Glaube', 'Gebet', 'Gemeinschaft', 'Orte'],
            Keyword::defaultOrder()->pluck('title')->all()
        );
        $this->assertTreeIsValid();
    }

    public function test_ancestors_descendants_children_siblings_and_depth_keep_their_meaning(): void
    {
        $root = $this->root('Root');
        $first = $this->child('First', $root);
        $leaf = $this->child('Leaf', $first);
        $second = $this->child('Second', $root);

        $this->assertSame(['Root', 'First'], $leaf->ancestors()->defaultOrder()->pluck('title')->all());
        $this->assertSame(['First', 'Leaf', 'Second'], $root->descendants()->defaultOrder()->pluck('title')->all());
        $this->assertSame(['First', 'Second'], $root->children()->defaultOrder()->pluck('title')->all());
        $this->assertSame(['Second'], $first->siblings()->defaultOrder()->pluck('title')->all());
        $this->assertSame(
            ['Deutsch', 'Englisch', 'Französisch', 'Root', 'First', 'Leaf', 'Second'],
            Keyword::withDepth()->defaultOrder()->pluck('title')->all()
        );
        $this->assertSame([0, 0, 0, 0, 1, 2, 1], Keyword::withDepth()->defaultOrder()->pluck('depth')->map(function ($depth) {
            return (int) $depth;
        })->all());
    }

    public function test_prepend_append_and_relative_insertion_define_sibling_order(): void
    {
        $root = $this->root('Root');
        $middle = $this->child('Middle', $root);
        $last = $this->child('Last', $root);

        $first = new Keyword(['title' => 'First']);
        $first->prependToNode($root)->save();

        $afterMiddle = new Keyword(['title' => 'After middle']);
        $afterMiddle->insertAfterNode($middle);

        $this->assertSame(
            ['First', 'Middle', 'After middle', 'Last'],
            $root->fresh()->children()->defaultOrder()->pluck('title')->all()
        );
        $this->assertTreeIsValid();
    }

    public function test_moving_a_subtree_to_another_parent_preserves_its_descendants(): void
    {
        $source = $this->root('Source');
        $branch = $this->child('Branch', $source);
        $leaf = $this->child('Leaf', $branch);
        $target = $this->root('Target');
        $targetChild = $this->child('Target child', $target);

        $branch->appendToNode($target)->save();

        $this->assertSame([], $source->fresh()->descendants()->pluck('title')->all());
        $this->assertSame(
            ['Target child', 'Branch', 'Leaf'],
            $target->fresh()->descendants()->defaultOrder()->pluck('title')->all()
        );
        $this->assertSame(['Target', 'Branch'], $leaf->fresh()->ancestors()->defaultOrder()->pluck('title')->all());
        $this->assertSame($target->id, $branch->fresh()->parent_id);
        $this->assertTreeIsValid();
    }

    public function test_assigning_parent_id_moves_a_node_as_used_by_the_keyword_api(): void
    {
        $firstRoot = $this->root('First root');
        $secondRoot = $this->root('Second root');
        $child = $this->child('Child', $firstRoot);

        $child->parent_id = $secondRoot->id;
        $child->save();

        $this->assertFalse($firstRoot->fresh()->descendants()->where('id', $child->id)->exists());
        $this->assertTrue($secondRoot->fresh()->children()->where('id', $child->id)->exists());
        $this->assertTreeIsValid();
    }

    public function test_descendants_and_self_returns_only_the_selected_branch_in_preorder(): void
    {
        $root = $this->root('Root');
        $branch = $this->child('Branch', $root);
        $leaf = $this->child('Leaf', $branch);
        $sibling = $this->child('Sibling', $root);

        $result = Keyword::descendantsAndSelf($branch->id)->pluck('id')->all();

        $this->assertSame([$branch->id, $leaf->id], $result);
        $this->assertNotContains($root->id, $result);
        $this->assertNotContains($sibling->id, $result);
    }

    public function test_moving_a_branch_to_root_preserves_the_subtree_and_places_it_after_existing_roots(): void
    {
        $root = $this->root('Root');
        $branch = $this->child('Branch', $root);
        $leaf = $this->child('Leaf', $branch);

        $branch->saveAsRoot();

        $this->assertNull($branch->fresh()->parent_id);
        $this->assertSame([], $root->fresh()->descendants()->pluck('id')->all());
        $this->assertSame([$leaf->id], $branch->fresh()->descendants()->pluck('id')->all());
        $this->assertSame('Branch', Keyword::whereIsRoot()->defaultOrder('desc')->first()->title);
        $this->assertTreeIsValid();
    }

    public function test_a_node_cannot_be_moved_below_its_own_descendant(): void
    {
        $root = $this->root('Root');
        $branch = $this->child('Branch', $root);
        $leaf = $this->child('Leaf', $branch);

        try {
            $root->appendToNode($leaf)->save();
            $this->fail('A cyclic nested-set move must be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('Node must not be a descendant.', $exception->getMessage());
        }

        $this->assertNull($root->fresh()->parent_id);
        $this->assertSame($root->id, $branch->fresh()->parent_id);
        $this->assertSame($branch->id, $leaf->fresh()->parent_id);
        $this->assertTreeIsValid();
    }

    public function test_descendant_materials_include_self_and_all_levels_but_no_sibling_and_are_distinct(): void
    {
        $user = User::factory()->create();
        $root = $this->root('Root');
        $branch = $this->child('Branch', $root);
        $leaf = $this->child('Leaf', $branch);
        $sibling = $this->child('Sibling', $root);
        $onRoot = $this->material('On root', $user);
        $onLeaf = $this->material('On leaf', $user);
        $onSibling = $this->material('On sibling', $user);

        $onRoot->keywords()->attach($root, ['relevance' => 101]);
        $onLeaf->keywords()->attach($leaf, ['relevance' => 202]);
        $onLeaf->keywords()->attach($branch, ['relevance' => 203]);
        $onSibling->keywords()->attach($sibling, ['relevance' => 255]);

        $this->assertEqualsCanonicalizing(
            [$onRoot->id, $onLeaf->id, $onSibling->id],
            $root->descendantMaterials()->pluck('materials.id')->all()
        );
        $this->assertSame(
            [$onLeaf->id],
            $branch->descendantMaterials()->pluck('materials.id')->all()
        );
    }

    public function test_nested_set_is_one_shared_forest_and_does_not_implicitly_scope_by_keyword_type(): void
    {
        $place = $this->root('Deutschland', 'place');
        $person = $this->child('Eine Person', $place, 'person');

        $this->assertSame($place->id, $person->fresh()->parent_id);
        $this->assertSame(['Eine Person'], $place->descendants()->pluck('title')->all());
        $this->assertTreeIsValid();
    }

    public function test_material_search_by_a_parent_keyword_matches_descendant_tags_and_authors_only(): void
    {
        $user = User::factory()->create();
        $root = $this->root('Root');
        $branch = $this->child('Branch', $root);
        $leaf = $this->child('Leaf', $branch);
        $siblingRoot = $this->root('Sibling root');
        $direct = $this->material('Direct', $user);
        $viaLeaf = $this->material('Via leaf', $user);
        $viaAuthor = $this->material('Via author', $user, $branch);
        $outside = $this->material('Outside', $user);
        $direct->keywords()->attach($root, ['relevance' => 100]);
        $viaLeaf->keywords()->attach($leaf, ['relevance' => 200]);
        $outside->keywords()->attach($siblingRoot, ['relevance' => 300]);

        $this->actingAs($user);
        $response = $this->postJson(route('pool.searchbar.get'), [
            'q' => [[['type' => 'k', 'id' => $root->id]]],
            'per_page' => 100,
        ]);

        $response->assertOk();
        $ids = array_column($response->json('data'), 'id');
        $this->assertEqualsCanonicalizing([$direct->id, $viaLeaf->id, $viaAuthor->id], $ids);
        $this->assertNotContains($outside->id, $ids);
    }

    public function test_merge_moves_children_material_relevance_and_authorship_to_the_surviving_node(): void
    {
        $user = User::factory()->create();
        $root = $this->root('Root');
        $survivor = $this->child('Survivor', $root);
        $duplicate = $this->child('Duplicate', $root);
        $duplicateChild = $this->child('Duplicate child', $duplicate);
        $taggedMaterial = $this->material('Tagged', $user);
        $authoredMaterial = $this->material('Authored', $user, $duplicate);
        $taggedMaterial->keywords()->attach($duplicate, ['relevance' => 237]);

        $result = resolve(KeywordHandlingService::class)->mergeKeywords($survivor, $duplicate);

        $this->assertSame($survivor->id, $result->id);
        $this->assertDatabaseMissing('keywords', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('keyword_material', [
            'material_id' => $taggedMaterial->id,
            'keyword_id' => $survivor->id,
            'relevance' => 237,
        ]);
        $this->assertSame($survivor->id, $authoredMaterial->fresh()->author_id);
        $this->assertSame($survivor->id, $duplicateChild->fresh()->parent_id);
        $this->assertTreeIsValid();
    }

    public function test_delete_without_children_preserves_the_complete_child_subtrees_at_the_same_level(): void
    {
        $root = $this->root('Root');
        $before = $this->child('Before', $root);
        $deleted = $this->child('Deleted', $root);
        $firstChild = $this->child('First child', $deleted);
        $grandchild = $this->child('Grandchild', $firstChild);
        $secondChild = $this->child('Second child', $deleted);
        $after = $this->child('After', $root);

        resolve(KeywordHandlingService::class)->deleteKeyword($deleted, false);

        $this->assertDatabaseMissing('keywords', ['id' => $deleted->id]);
        $this->assertSame($root->id, $firstChild->fresh()->parent_id);
        $this->assertSame($root->id, $secondChild->fresh()->parent_id);
        $this->assertSame($firstChild->id, $grandchild->fresh()->parent_id);
        $this->assertSame(
            ['Before', 'Second child', 'First child', 'Grandchild', 'After'],
            $root->fresh()->descendants()->defaultOrder()->pluck('title')->all()
        );
        $this->assertTreeIsValid();
    }

    public function test_delete_with_children_removes_the_complete_subtree_only(): void
    {
        $root = $this->root('Root');
        $deleted = $this->child('Deleted', $root);
        $child = $this->child('Child', $deleted);
        $leaf = $this->child('Leaf', $child);
        $kept = $this->child('Kept', $root);

        resolve(KeywordHandlingService::class)->deleteKeyword($deleted, true);

        $this->assertDatabaseMissing('keywords', ['id' => $deleted->id]);
        $this->assertDatabaseMissing('keywords', ['id' => $child->id]);
        $this->assertDatabaseMissing('keywords', ['id' => $leaf->id]);
        $this->assertDatabaseHas('keywords', ['id' => $root->id]);
        $this->assertDatabaseHas('keywords', ['id' => $kept->id, 'parent_id' => $root->id]);
        $this->assertTreeIsValid();
    }

    public function test_serialization_keeps_tree_boundaries_private_but_exposes_parent_and_loaded_relations(): void
    {
        $root = $this->root('Root');
        $child = $this->child('Child', $root);

        $serialized = $child->fresh()->load(['ancestors', 'descendants'])->toArray();

        $this->assertArrayNotHasKey('_lft', $serialized);
        $this->assertArrayNotHasKey('_rgt', $serialized);
        $this->assertSame($root->id, $serialized['parent_id']);
        $this->assertSame([$root->id], array_column($serialized['ancestors'], 'id'));
        $this->assertSame([], $serialized['descendants']);
    }

    private function root(string $title, string $type = 'key'): Keyword
    {
        $keyword = new Keyword(['title' => $title, 'type' => $type]);
        $keyword->saveAsRoot();

        return $keyword->fresh();
    }

    private function child(string $title, Keyword $parent, string $type = 'key'): Keyword
    {
        $keyword = new Keyword(['title' => $title, 'type' => $type]);
        $keyword->appendToNode($parent)->save();

        return $keyword->fresh();
    }

    private function material(string $title, User $user, ?Keyword $author = null): Material
    {
        $material = new Material(['title' => $title]);
        $material->created_by = $user->id;
        $material->modified_by = $user->id;
        $material->author_id = optional($author)->id;
        $material->save();

        return $material;
    }

    private function assertTreeRows(array $expected): void
    {
        $actual = Keyword::query()
            ->orderBy('_lft')
            ->get(['id', 'parent_id', '_lft', '_rgt'])
            ->map(function (Keyword $keyword) {
                return [
                    $keyword->id,
                    $keyword->parent_id,
                    (int) $keyword->_lft,
                    (int) $keyword->_rgt,
                ];
            })
            ->all();

        $this->assertSame($expected, $actual);
    }

    private function assertTreeIsValid(): void
    {
        $this->assertSame([
            'oddness' => 0,
            'duplicates' => 0,
            'wrong_parent' => 0,
            'missing_parent' => 0,
        ], Keyword::query()->countErrors());
        $this->assertFalse(Keyword::query()->isBroken());
    }
}
