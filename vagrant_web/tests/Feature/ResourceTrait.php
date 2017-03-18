<?php
namespace Tests\Feature;


use App\Models\ForeignInstance;
use App\Models\ForeignResourceKey;
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Resource;
use Illuminate\Http\UploadedFile;

Trait ResourceTrait {

	protected function setUpTestData() {

		$tesKw = Keyword::firstOrCreate(['title' => 'Test']);
		$tesKw->save();

		/** @var Collection $resourceInstances */
		$resourceInstances = factory(ForeignInstance::class, 3)->create();
		$keywords          = factory(Keyword::class, 5)->create();

		factory(Resource::class, 2)->create()->each(function (Resource $r) use ($tesKw) {
			/** @var Material $material */
			$material = $r->materials()->save(factory(Material::class)->create());
			$material->keywords()->save($tesKw);
		});

		factory(ImageFile::class, 5)->create()->each(function ($r) use ($tesKw, $keywords) {
			/** @var Material $material */
			$material = $r->materials()->save(factory(Material::class)->make());
			$material->keywords()->save($tesKw, ['rating' => rand(0, 255)]);
			$material->keywords()->attach($keywords->pluck('id'));
		});

		Resource::all()->each(function (Resource $r) use ($resourceInstances) {

			$fi = $resourceInstances->first();

			$remoteKey                      = new ForeignResourceKey();
			$remoteKey->resource_id         = $r->id;
			$remoteKey->foreign_instance_id = $fi->id;
			$remoteKey->remote_id           = rand(1, 999999);

			$remoteKey->save();
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