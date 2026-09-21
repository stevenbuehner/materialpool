<?php

namespace Database\Seeders;

use App\Models\AudioFile;
use App\Models\Bibleverse;
use App\Models\DocumentFile;
use App\Models\File;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\PdfFile;
use App\Models\Resource;
use App\Models\Text;
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
		User::factory([
			'name'           => 'admin',
			'password'       => bcrypt('admin'),
			'email'          => 'admin@test.app',
			'remember_token' => 'i6VuECaXTUHgjHwvdVemEtyu6nPxx90y3Qva9eFNhMgDk5PSKMLrCuCBck4s',
			'is_admin'       => TRUE
		])
			->count(1)
			->create();

		User::factory()
			->count(10)
			->create();

		Keyword::factory()
			->count(20)
			->create();


		File::factory()
			->count(2)
			->afterCreating(function (Resource $r) {

				$material = self::makeMaterialWithRandomUser();
				$material->save();
				$material = $r->materials()->save($material);

				$material->keywords()->saveMany(Keyword::factory()->count(5)->create());

				$bibleverses = Bibleverse::factory()
					->count(5)
					->create();

				$bibleverses->each(function (Bibleverse $b) use ($material) {
					$bv = \App\Models\Bibleverse::firstOrCreate(['from' => $b->from, 'to' => $b->to]);
					$material->bibleverses()
						->attach($bv->id,
							['relevance' => rand(1,
								64)]);

					return $bv;
				});

				self::addRandomMaterialUid($material, $material->creator);
				self::addRandomResourceUid($r, $material->creator);

			})
			->create();

		// Resource wihtout material
		File::factory()
			->count(2)
			->create();

		AudioFile::factory()
			->count(5)
			->create()
			->each(function (AudioFile $r) {

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


		VideoFile::factory()
			->count(5)
			->create()
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


		ImageFile::factory()
			->count(5)
			->create()
			->each(function (ImageFile $r) {
				$material = self::makeMaterialWithRandomUser();
				$material->save();
				$material->keywords()
					->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);

				self::addRandomMaterialUid($material, $material->creator);
				self::addRandomResourceUid($r, $material->creator);
			});


		// Document1 and PDF1 are intentionally seeded twice. The duplicate-resource queue
		// must merge their assigned materials into the resource with the smaller ID.
		DocumentFile::factory()
			->fromTestFile('Document1.docx')
			->count(2)
			->create(['created_by' => User::all()->offsetGet(5)->id])
			->each(fn (DocumentFile $resource) => self::attachPageLimitedMaterial($resource));
		DocumentFile::factory()
			->fromTestFile('Document2.docx')
			->count(1)
			->create()
			->each(fn (DocumentFile $resource) => self::attachPageLimitedMaterial($resource));
		DocumentFile::factory()
			->fromTestFile('Document3.docx')
			->count(1)
			->create()
			->each(fn (DocumentFile $resource) => self::attachPageLimitedMaterial($resource));
		DocumentFile::factory()
			->withMissingLocalFile('Nicht-vorhandenes-Testdokument.docx')
			->count(1)
			->create()
			->each(fn (DocumentFile $resource) => self::attachPageLimitedMaterial($resource));

		PdfFile::factory()
			->fromTestFile('PDF1.pdf')
			->count(2)
			->create()
			->each(fn (PdfFile $resource) => self::attachPageLimitedMaterial($resource));
		PdfFile::factory()
			->fromTestFile('PDF2.pdf')
			->count(1)
			->create()
			->each(fn (PdfFile $resource) => self::attachPageLimitedMaterial($resource));
		PdfFile::factory()
			->fromTestFile('PDF3.pdf')
			->count(1)
			->create()
			->each(fn (PdfFile $resource) => self::attachPageLimitedMaterial($resource));

		Text::factory()
			->count(5)
			->create()
			->each(function (Text $r) {
				$material = Material::factory()->create();
				$material->resources()->attach($r);
				$material->keywords()->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);
			});

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
		return Material::factory()
			->make([
				'created_by'  => $user_id,
				'modified_by' => $user_id
			]);
	}

	public static function addRandomMaterialUid(Material $material, User $user) {
		$fk = ForeignMaterialId::factory()
			->make();
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
		$fk = ForeignResourceId::factory()
			->make();
		$fk->resource()->associate($resource);
		$fk->user()->associate($user);
		$fk->save();

		return $fk;
	}

	private static function attachPageLimitedMaterial(DocumentFile|PdfFile $resource): void {
		$material = self::makeMaterialWithRandomUser();
		$material->save();
		$limitation = new \App\ResourceLimitations\PageLimitation();
		$limitation->setPages([1]);
		$material->resources()->attach($resource, ['limitation' => $limitation]);
		$material->keywords()->save(self::getRandomKeyword(), ['relevance' => rand(0, 255)]);

		self::addRandomMaterialUid($material, $material->creator);
		self::addRandomResourceUid($resource, $material->creator);
	}

	/**
	 * @return Keyword
	 */
	static function getRandomKeyword() {
		$kw = Keyword::orderByRaw('RAND()')->take(1)->first();;

		return $kw;
	}


}
