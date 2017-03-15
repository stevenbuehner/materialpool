<?php

use App\Models\AudioFile;
use App\Models\DocumentFile;
use App\Models\ForeignInstance;
use App\Models\ForeignResourceKey;
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Language;
use App\Models\Material;
use App\Models\Person;
use App\Models\Place;
use App\Models\Resource;
use App\Models\VideoFile;
use Illuminate\Database\Seeder;


class ResourceSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {

		$tesKw = Keyword::firstOrCreate(['title' => 'Test']);
		$tesKw->save();

		factory(Keyword::class, 5)->create();
		factory(Person::class, 5)->create();
		factory(Language::class, 5)->create();
		factory(Place::class, 5)->create();
		factory(ForeignInstance::class, 3)->create();

		factory(Resource::class, 2)->create()->each(function (Resource $r) use ($tesKw) {
			/** @var Material $material */
			$material = $r->materials()->save(factory(Material::class)->create());
			$material->keywords()->save($tesKw);
		});

		factory(AudioFile::class, 5)->create()->each(function ($r) {
			$material             = $r->materials()->save(factory(Material::class)->make());
			$material->limitation = new \App\ResourceLimitations\TimeLimitation(0, 299);
			$material->save();
			$material->keywords()->save(self::getRandomKeyword(), ['rating' => rand(0, 255)]);
		});
		factory(VideoFile::class, 5)->create()->each(function ($r) {
			$material             = $r->materials()->save(factory(Material::class)->make());
			$material->limitation = new \App\ResourceLimitations\TimeLimitation(0, 299);
			$material->save();
			$material->keywords()->save(self::getRandomKeyword(), ['rating' => rand(0, 255)]);
		});
		factory(ImageFile::class, 5)->create()->each(function ($r) {
			$material = $r->materials()->save(factory(Material::class)->make());
			$material->keywords()->save(self::getRandomKeyword(), ['rating' => rand(0, 255)]);
		});
		factory(DocumentFile::class, 5)->create()->each(function ($r) {
			$material             = $r->materials()->save(factory(Material::class)->make());
			$material->limitation = new \App\ResourceLimitations\PageLimitation(5, 10);
			$material->save();
			$material->keywords()->save(self::getRandomKeyword(), ['rating' => rand(0, 255)]);
		});


		Resource::all()->each(function (Resource $r) {
			$fi = self::getRandomForeignInstance();

			$remoteKey                      = new ForeignResourceKey();
			$remoteKey->resource_id         = $r->id;
			$remoteKey->foreign_instance_id = $fi->id;
			$remoteKey->remote_id           = rand(1, 999999);

			$remoteKey->save();
		});

	}

	static function getRandomKeyword() {
		$kw = Keyword::orderByRaw('RAND()')->take(1)->first();;

		return $kw;
	}

	/**
	 * @return ForeignInstance
	 */
	static function getRandomForeignInstance() {
		$fi = ForeignInstance::orderByRaw('RAND()')->take(1)->first();;

		return $fi;
	}
}
