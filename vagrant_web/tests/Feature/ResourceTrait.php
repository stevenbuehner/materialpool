<?php
namespace Tests\Feature;


use App\Models\ForeignInstance;
use App\Models\ForeignResourceKey;
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

Trait ResourceTrait {

	protected function setUpTestData() {

		$tesKw = Keyword::firstOrCreate(['title' => 'Test']);
		$tesKw->save();

		/** @var Collection $resourceInstances */
		/** @var Collection $users */
		$users             = factory(User::class, 3)->create();
		$resourceInstances = factory(ForeignInstance::class, 3)->make()
															   ->each(function (ForeignInstance $fi, $i) use ($users) {
																   $fi->user_id = $users->offsetGet($i)->id;
																   $fi->save();
															   });
		$keywords          = factory(Keyword::class, 5)->create();

		factory(Resource::class, 2)
			->create([
						 'created_by' => $users->offsetGet(0)->first()->id
					 ])
			->each(function (Resource $r) use ($tesKw, $users) {
				/** @var Material $material */
				$material = \ResourceSeeder::makeMaterialWithUserId($users->offsetGet(0)->id);
				$material->save();
				$material->keywords()->save($tesKw);
				$r->materials()->save($material);
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
				$material->keywords()->save($tesKw, ['rating' => rand(0, 255)]);
				$material->keywords()->attach($keywords->pluck('id'));
				$r->materials()->save($material);
			});

		Resource::all()
				->each(function (Resource $r) use ($resourceInstances) {

					$fi = $resourceInstances->filter(function ($fi) use ($r) {
						return $fi->user_id === $r->created_by;
					})->first();

					if ($fi) {
						$remoteKey                      = new ForeignResourceKey();
						$remoteKey->resource_id         = $r->id;
						$remoteKey->foreign_instance_id = $fi->id;
						$remoteKey->remote_id           = rand(1, 999999);
						$remoteKey->save();
					}
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