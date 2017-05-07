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
use App\Models\User;
use App\Models\VideoFile;
use Illuminate\Database\Seeder;


class ResourceSeeder extends Seeder {

	/**
	 * @return ForeignInstance
	 */
	static function getRandomForeignInstance() {
		$fi = ForeignInstance::orderByRaw('RAND()')->take(1)->first();;

		return $fi;
	}

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {

		$tesKw = Keyword::firstOrCreate(['title' => 'Test']);
		$tesKw->save();

		// Create Testadmin
		factory(User::class)->create(['name' => 'admin', 'password' => bcrypt('admin'), 'email' => 'admin@test.app']);

		factory(Keyword::class, 5)->create();
		factory(Person::class, 5)->create();
		factory(Language::class, 5)->create();
		factory(Place::class, 5)->create();
		factory(ForeignInstance::class)->create(['user_id' => factory(User::class)->create()->id]);
		factory(ForeignInstance::class)->create(['user_id' => factory(User::class)->create()->id]);
		factory(ForeignInstance::class)->create(['user_id' => factory(User::class)->create()->id]);
		factory(User::class, 5)->create();

		factory(Resource::class, 2)
			->create(['created_by' => User::all()->offsetGet(1)->id])
			->each(function (Resource $r) use ($tesKw) {
				/** @var Material $material */
				$material = self::makeMaterialWithRandomUser();
				$material->save();
				$material = $r->materials()->save($material);
				$material->keywords()->save($tesKw);

				$bibleverses = factory(\App\Models\Bibleverse::class, 'Genesis', 5)
					->make()
					->each(function (\App\Models\Bibleverse $b) use ($material) {
						$bv = \App\Models\Bibleverse::firstOrCreate(['from' => $b->from, 'to' => $b->to]);
						$material->bibleverses()
								 ->attach($bv->id,
										  ['relevance' => rand(1,
															   64)]);

						return $bv;
					});
			});

		factory(AudioFile::class, 5)->create(['created_by' => User::all()->offsetGet(2)->id])->each(function ($r) {
			$material             = self::makeMaterialWithRandomUser();
			$material->limitation = new \App\ResourceLimitations\TimeLimitation(0, 299);
			$material->save();
			$material->keywords()->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);
		});
		factory(VideoFile::class, 5)->create(['created_by' => User::all()->offsetGet(3)->id])->each(function ($r) {
			$material             = self::makeMaterialWithRandomUser();
			$material->limitation = new \App\ResourceLimitations\TimeLimitation(0, 299);
			$material->save();
			$material->keywords()->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);
		});
		factory(ImageFile::class, 5)->create(['created_by' => User::all()->offsetGet(4)->id])->each(function ($r) {
			$material = self::makeMaterialWithRandomUser();
			$material->save();
			$material->keywords()->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);
		});
		factory(DocumentFile::class, 5)->create(['created_by' => User::all()->offsetGet(5)->id])->each(function ($r) {
			$material             = self::makeMaterialWithRandomUser();
			$material->limitation = new \App\ResourceLimitations\PageLimitation(5, 10);
			$material->save();
			$material->keywords()->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);
		});

		$counter = 100;
		Resource::all()->each(function (Resource $r) use (&$counter) {
			// $fi        = self::getRandomForeignInstance();

			$fi = ForeignInstance::where([
											 'user_id' => $r->created_by
										 ])->take(1)->get()->first();


			if ($fi) {
				$remoteKey                      = new ForeignResourceKey();
				$remoteKey->resource_id         = $r->id;
				$remoteKey->foreign_instance_id = $fi->id;
				$remoteKey->remote_id           = $counter++;
				$remoteKey->save();
			}
		});

	}

	public static function makeMaterialWithRandomUser() {
		$user = self::getRandomUser();

		return self::makeMaterialWithUserId($user->id);
	}

	/**
	 * @return User
	 */
	static function getRandomUser() {
		$user = User::orderByRaw('RAND()')->take(1)->first();;

		return $user;
	}

	public static function makeMaterialWithUserId($user_id) {
		return factory(Material::class)
			->make([
					   'created_by'  => $user_id,
					   'modified_by' => $user_id
				   ]);
	}

	/**
	 * @return Keyword
	 */
	static function getRandomKeyword() {
		$kw = Keyword::orderByRaw('RAND()')->take(1)->first();;

		return $kw;
	}


}
