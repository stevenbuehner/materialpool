<?php

use App\Models\AudioFile;
use App\Models\DocumentFile;
use App\Models\ImageFile;
use App\Models\Material;
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

		factory(Resource::class, 2)->create()->each(function ($r) {
			$r->materials()->save(factory(Material::class)->make());
			$r->materials()->save(factory(Material::class)->make());
		});
		factory(AudioFile::class, 5)->create()->each(function ($r) {
			$r->materials()->save(factory(Material::class)->make());
		});
		factory(VideoFile::class, 5)->create()->each(function ($r) {
			$r->materials()->save(factory(Material::class)->make());
		});
		factory(ImageFile::class, 5)->create()->each(function ($r) {
			$r->materials()->save(factory(Material::class)->make());
		});
		factory(DocumentFile::class, 5)->create()->each(function ($r) {
			$r->materials()->save(factory(Material::class)->make());
		});


	}
}
