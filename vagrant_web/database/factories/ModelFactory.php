<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

use App\Models\AudioFile;
use App\Models\DocumentFile;
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


/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(User::class, function (Faker\Generator $faker) {
	static $password;

	return [
		'name'           => $faker->name,
		'email'          => $faker->unique()->safeEmail,
		'password'       => $password ?: $password = bcrypt('secret'),
		'remember_token' => str_random(10),
	];
});

$factory->define(Material::class, function (Faker\Generator $faker) {

	return [
		'title'       => $faker->text(255),
		'description' => $faker->sentences(2, TRUE),
		'from_bot'    => $faker->boolean(),
		'rating'      => rand(0, 20)
	];
});


$factory->define(Resource::class, function (Faker\Generator $faker) {

	return [
		'remote_path'  => 'https://www.allmystery.de/static/upics/942586_handy.jpg',
		'local_path'   => 'some/file/path',
		'content_hash' => 'just a fake hash',
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(AudioFile::class, function (Faker\Generator $faker) {

	return [
		'remote_path'  => 'http://some/file/path',
		'local_path'   => 'some/file/path',
		'content_hash' => 'just a fake hash',
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()

	];
});

$factory->define(VideoFile::class, function (Faker\Generator $faker) {

	return [
		'remote_path'  => 'http://some/file/path',
		'local_path'   => 'some/file/path',
		'content_hash' => 'just a fake hash',
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(ImageFile::class, function (Faker\Generator $faker) {

	$testStorage = Storage::disk(config('app.disks.testfiles'));
	$liveStorage = Storage::disk(config('app.disks.resources'));

	$src        = $testStorage->read('Bild.jpg');
	$targetPath = uniqid('testing/') . '.jpg';
	$liveStorage->write($targetPath, $src);

	return [
		'remote_path'       => 'https://www.allmystery.de/static/upics/942586_handy.jpg',
		'local_path'        => config('app.disks.resources') . '::' . $targetPath,
		'content_hash'      => 'just a fake hash',
		'notes'             => $faker->sentences(3, TRUE),
		'is_public'         => $faker->boolean(),
		'original_filename' => 'Ich bin ein Dateiname.jpg'
	];
});

$factory->define(DocumentFile::class, function (Faker\Generator $faker) {

	return [
		'remote_path'  => 'http://some/file/path',
		'local_path'   => 'some/file/path',
		'content_hash' => 'just a fake hash',
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(PdfFile::class, function (Faker\Generator $faker) {

	$testStorage = Storage::disk(config('app.disks.testfiles'));
	$liveStorage = Storage::disk(config('app.disks.resources'));

	$src        = $testStorage->read('PDF.pdf');
	$targetPath = uniqid('testing/') . '.pdf';
	$liveStorage->write($targetPath, $src);

	return [
		'remote_path'  => 'http://www.ubtech.eu/wp-content/uploads/2013/02/BuecherBLUB.pdf',
		'local_path'   => config('app.disks.resources') . '::' . $targetPath,
		'content_hash' => 'just a fake hash',
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(Text::class, function (Faker\Generator $faker) {
	$content = 'Person: ' . $faker->name . ';';
	$content .= 'Title: ' . $faker->title . ';';
	$content .= 'vom: ' . $faker->date() . ';';

	$content .= "\n" . $faker->sentences(5, TRUE);

	return [
		'remote_path'  => NULL,
		'local_path'   => NULL,
		'content_hash' => 'just a fake hash',
		'content'      => $content,
		'notes'        => $faker->text(),
		'is_public'    => $faker->boolean()
	];
});


$factory->define(Keyword::class, function (Faker\Generator $faker) {
	return [
		'title' => $faker->unique()->word,
		'type'  => 'key'
	];
});

$factory->define(Keyword::class, function (Faker\Generator $faker) {
	return [
		'title' => $faker->unique()->name,
		'type'  => 'person'
	];
});

$factory->define(Keyword::class, function (Faker\Generator $faker) {
	return [
		'title' => $faker->unique()->languageCode,
		'type'  => 'lang'
	];
});

$factory->define(Keyword::class, function (Faker\Generator $faker) {
	return [
		'title' => $faker->unique()->city,
		'type'  => 'place'
	];
});


$factory->define(ForeignMaterialId::class, function (Faker\Generator $faker) {
	return [
		'foreign_id' => $faker->unique()->uuid
	];
});

$factory->define(ForeignResourceId::class, function (Faker\Generator $faker) {
	return [
		'foreign_id' => $faker->unique()->uuid
	];
});


$factory->define(\App\Models\Bibleverse::class, function (Faker\Generator $faker) {
	$minFromChapter = rand(1, 50);
	$minVerse       = rand(1, 18);

	return [
		'book_id'      => 1,
		'from_chapter' => $minFromChapter,
		'to_chapter'   => rand($minFromChapter, 50),
		'from_verse'   => $minVerse,
		'to_verse'     => rand($minVerse, 18)
	];
}, 'Genesis');

