<?php

use App\Models\AudioFile;
use App\Models\DocumentFile;
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

		factory(Keyword::class, 5)->create();
		factory(Person::class, 5)->create();
		factory(Language::class, 5)->create();
		factory(Place::class, 5)->create();

		factory(Resource::class, 2)->create()->each(function (Resource $r) {
			$material = $r->materials()->save(factory(Material::class)->create());
			$material->keywords()->save(self::getRandomKeyword());
		});

		factory(AudioFile::class, 5)->create()->each(function ($r) {
			$material = $r->materials()->save(factory(Material::class)->make());
			$material->keywords()->save(self::getRandomKeyword());
		});
		factory(VideoFile::class, 5)->create()->each(function ($r) {
			$material = $r->materials()->save(factory(Material::class)->make());
			$material->keywords()->save(self::getRandomKeyword());
		});
		factory(ImageFile::class, 5)->create()->each(function ($r) {
			$material = $r->materials()->save(factory(Material::class)->make());
			$material->keywords()->save(self::getRandomKeyword());
		});
		factory(DocumentFile::class, 5)->create()->each(function ($r) {
			$material = $r->materials()->save(factory(Material::class)->make());
			$material->keywords()->save(self::getRandomKeyword());
		});


	}

	static function getRandomKeyword() {
		$kw = Keyword::orderByRaw('RAND()')->take(1)->first();;

		return $kw;
	}
}
