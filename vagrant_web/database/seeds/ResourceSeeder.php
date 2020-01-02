<?php

use App\Models\AudioFile;
use App\Models\DocumentFile;
use App\Models\ForeignResourceId;
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Language;
use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Person;
use App\Models\Place;
use App\Models\Resource;
use App\Models\User;
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

		// Create Testadmin
		factory(User::class)->create([
			'name'           => 'admin',
			'password'       => bcrypt('admin'),
			'email'          => 'admin@test.app',
			'remember_token' => 'i6VuECaXTUHgjHwvdVemEtyu6nPxx90y3Qva9eFNhMgDk5PSKMLrCuCBck4s',
			'is_admin'       => TRUE
		]);

		factory(Keyword::class, 20)->create();
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

				self::addRandomMaterialUid($material, $material->creator);
				self::addRandomResourceUid($r, $material->creator);
			});


		factory(AudioFile::class, 5)
			->create(['created_by' => User::all()
				->offsetGet(2)->id])
			->each(function (AudioFile $r) {

				/** @var Material $material */
				$material = self::makeMaterialWithRandomUser();
				$material->save();
				$material->resources()
					->attach($r,
						['limitation' => new \App\ResourceLimitations\TimeLimitation()]);

				$material->keywords()
					->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);

				self::addRandomMaterialUid($material, $material->creator);
				self::addRandomResourceUid($r, $material->creator);
			});


		factory(VideoFile::class, 5)
			->create(['created_by'               => User::all()
				->offsetGet(3)->id, 'local_path' => 'resources::1/video/uAUN7GA7kZyTzfhoEcfKxApvzLGiPMbzdQi367LK.mp4'])
			->each(function (VideoFile $r) {
				$material = self::makeMaterialWithRandomUser();
				$material->save();
				$material->resources()
					->attach($r,
						['limitation' => new \App\ResourceLimitations\TimeLimitation()]);
				$material->keywords()->save(self::getRandomKeyword(), ['relevance' => rand(0,
					255)]);

				self::addRandomMaterialUid($material, $material->creator);
				self::addRandomResourceUid($r, $material->creator);
			});


		factory(ImageFile::class, 5)
			->create(['created_by' => User::all()->offsetGet(4)->id])
			->each(function (ImageFile $r) {
				$material = self::makeMaterialWithRandomUser();
				$material->save();
				$material->keywords()
					->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);

				self::addRandomMaterialUid($material, $material->creator);
				self::addRandomResourceUid($r, $material->creator);
			});


		factory(DocumentFile::class, 5)
			->create(['created_by' => User::all()->offsetGet(5)->id])
			->each(function (DocumentFile $r) {
				$material = self::makeMaterialWithRandomUser();
				$material->save();
				$limitation = new \App\ResourceLimitations\PageLimitation();
				$limitation->setPages([1, 3, 4, 5]);
				$material->resources()
					->attach($r, ['limitation' => $limitation]);
				$material->keywords()->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);

				self::addRandomMaterialUid($material, $material->creator);
				self::addRandomResourceUid($r, $material->creator);
			});

		factory(PdfFile::class, 5)
			->create(['created_by'               => User::all()
				->offsetGet(5)->id, 'local_path' => 'resources::1/pdf/4dt6tOhunfEwMMI1HFzeCVOKJW9GE1vOcZtjeuDy.pdf'])
			->each(function (PdfFile $r) {
				$material = self::makeMaterialWithRandomUser();
				$material->save();
				$limitation = new \App\ResourceLimitations\PageLimitation();
				$limitation->setPages([1, 3, 4, 5]);
				$material->resources()
					->attach($r, ['limitation' => $limitation]);
				$material->keywords()->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);

				self::addRandomMaterialUid($material, $material->creator);
				self::addRandomResourceUid($r, $material->creator);
			});

		$counter = 100;
	}

	/**
	 * @return Material
	 */
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

	/**
	 * @param $user_id
	 * @return Material
	 */
	public static function makeMaterialWithUserId($user_id) {
		return factory(Material::class)
			->make([
				'created_by'  => $user_id,
				'modified_by' => $user_id
			]);
	}

	public static function addRandomMaterialUid(Material $material, User $user) {
		$fk = factory(\App\Models\ForeignMaterialId::class)->make();
		$fk->material()->associate($material);
		$fk->user()->associate($user);
		$fk->save();

		return $fk;
	}

	/**
	 * @param Resource $resource
	 * @param User $user
	 * @return ForeignResourceId
	 */
	public static function addRandomResourceUid(Resource $resource, User $user) {
		/** @var \App\Models\ForeignResourceId $fk */
		$fk = factory(\App\Models\ForeignResourceId::class)->make();
		$fk->resource()->associate($resource);
		$fk->user()->associate($user);
		$fk->save();

		return $fk;
	}

	/**
	 * @return Keyword
	 */
	static function getRandomKeyword() {
		$kw = Keyword::orderByRaw('RAND()')->take(1)->first();;

		return $kw;
	}


}
