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
use App\Models\ImageFile;
use App\Models\Keyword;
use App\Models\Language;
use App\Models\Material;
use App\Models\Person;
use App\Models\Place;
use App\Models\Resource;
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
		'title'       => $faker->title,
		'description' => $faker->sentences(2, TRUE),
		'from_bot'    => $faker->boolean()
	];
});


$factory->define(Resource::class, function (Faker\Generator $faker) {
	static $secret;

	return [
		'path'         => 'some/file/path',
		'content_hash' => $secret ?: $secret = sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(AudioFile::class, function (Faker\Generator $faker) {
	static $secret;

	return [
		'path'         => 'some/file/path',
		'content_hash' => $secret ?: $secret = sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()

	];
});

$factory->define(VideoFile::class, function (Faker\Generator $faker) {
	static $secret;

	return [
		'path'         => 'some/file/path',
		'content_hash' => $secret ?: $secret = sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(ImageFile::class, function (Faker\Generator $faker) {
	static $secret;

	return [
		'path'         => 'some/file/path',
		'content_hash' => $secret ?: $secret = sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(DocumentFile::class, function (Faker\Generator $faker) {
	static $secret;

	return [
		'path'         => 'some/file/path',
		'content_hash' => $secret ?: $secret = sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
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
