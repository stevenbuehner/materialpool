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
use App\Models\ForeignInstance;
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Language;
use App\Models\Material;
use App\Models\Person;
use App\Models\Place;
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
		'rating'      => rand(0, 64)
	];
});


$factory->define(Resource::class, function (Faker\Generator $faker) {
	static $secret;

	return [
		'remote_path'  => 'https://www.allmystery.de/static/upics/942586_handy.jpg',
		'local_path'   => 'some/file/path',
		'content_hash' => $secret ?: $secret = sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(AudioFile::class, function (Faker\Generator $faker) {
	static $secret;

	return [
		'remote_path'  => 'http://some/file/path',
		'local_path'   => 'some/file/path',
		'content_hash' => $secret ?: $secret = sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()

	];
});

$factory->define(VideoFile::class, function (Faker\Generator $faker) {
	static $secret;

	return [
		'remote_path'  => 'http://some/file/path',
		'local_path'   => 'some/file/path',
		'content_hash' => $secret ?: $secret = sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(ImageFile::class, function (Faker\Generator $faker) {
	static $secret;

	return [
		'remote_path'       => 'https://www.allmystery.de/static/upics/942586_handy.jpg',
		'local_path'        => 'some/file/path',
		'content_hash'      => $secret ?: $secret = sha1('secret'),
		'notes'             => $faker->sentences(3, TRUE),
		'is_public'         => $faker->boolean(),
		'original_filename' => 'Ich bin ein Dateiname.jpg'
	];
});

$factory->define(DocumentFile::class, function (Faker\Generator $faker) {
	static $secret;

	return [
		'remote_path'  => 'http://some/file/path',
		'local_path'   => 'some/file/path',
		'content_hash' => $secret ?: $secret = sha1('secret'),
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
		'content_hash' => sha1($content),
		'content'      => $content,
		'notes'        => '',
		'is_public'    => $faker->boolean()
	];
});


$factory->define(Keyword::class, function (Faker\Generator $faker) {
	return [
		'title' => $faker->unique()->word,
		'type'  => 'key'
	];
});

$factory->define(Person::class, function (Faker\Generator $faker) {
	return [
		'title' => $faker->unique()->name,
		'type'  => 'person'
	];
});

$factory->define(Language::class, function (Faker\Generator $faker) {
	return [
		'title' => $faker->unique()->languageCode,
		'type'  => 'lang'
	];
});

$factory->define(Place::class, function (Faker\Generator $faker) {
	return [
		'title' => $faker->unique()->city,
		'type'  => 'place'
	];
});


$factory->define(ForeignInstance::class, function (Faker\Generator $faker) {
	return [
		'name'    => $faker->unique()->name,
		'api_key' => preg_replace('~\.|\s|!\?~', '', $faker->unique()->text(50)),
		'info'    => $faker->sentences(1, TRUE)
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

