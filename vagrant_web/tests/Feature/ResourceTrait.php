<?php

namespace Tests\Feature;


use App\Jobs\UpdateResourceHashes;
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Resource;
use App\Models\Text;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

Trait ResourceTrait {

	protected function setUpTestData() {

		$tesKw = Keyword::firstOrCreate(['title' => 'Test']);
		$tesKw->save();

		/** @var Collection $resourceInstances */
		/** @var Collection $users */
		$users    = factory(User::class, 3)->create();
		$keywords = factory(Keyword::class, 5)->create();

		factory(Text::class, 2)
			->create([
						 'created_by' => $users->offsetGet(0)->first()->id
					 ])
			->each(function (Resource $r) use ($tesKw, $users) {
				/** @var Material $material */
				$material = \ResourceSeeder::makeMaterialWithUserId($users->offsetGet(0)->id);
				$material->save();
				$material->keywords()->save($tesKw);
				$r->materials()->save($material);

				\ResourceSeeder::addRandomMaterialUid($material, $material->creator);

				UpdateResourceHashes::dispatch($r);
			});

		factory(ImageFile::class, 5)
			->create([
						 'created_by' => $users->offsetGet(1)->id
					 ])
			->each(function ($r) use ($tesKw, $keywords, $users) {
				/** @var Material $material */
				/** @var Material $material */
				$material = \ResourceSeeder::makeMaterialWithUserId($users->offsetGet(1)->id);
				$material->save();
				$material->keywords()->save($tesKw, ['relevance' => rand(0, 255)]);
				$material->keywords()->attach($keywords->pluck('id'));
				$r->materials()->save($material);

				\ResourceSeeder::addRandomMaterialUid($material, $material->creator);

				UpdateResourceHashes::dispatch($r);
			});
	}

	protected function getImageUploadData() {
		return $data = [
			'file'      => UploadedFile::fake()->image('avatar.jpg', 100, 100),
			'remote_id' => 10,
			'is_public' => TRUE,
			'notes'     => 'Some notes'
		];
	}

}