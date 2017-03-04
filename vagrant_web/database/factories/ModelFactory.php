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

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {
	static $password;

	return [
		'name'           => $faker->name,
		'email'          => $faker->unique()->safeEmail,
		'password'       => $password ?: $password = bcrypt('secret'),
		'remember_token' => str_random(10),
	];
});

$factory->define(\App\Material::class, function (Faker\Generator $faker) {
	static $password;

	return [
		'title'       => $faker->title,
		'description' => $faker->sentences(2, TRUE),
		'from_bot'    => $faker->boolean()
	];
});


$factory->define(\App\Resource::class, function (Faker\Generator $faker) {
	static $password;

	return [
		'path'         => 'some/file/path',
		'content_hash' => sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(\App\AudioFile::class, function (Faker\Generator $faker) {
	static $password;

	return [
		'path'         => 'some/file/path',
		'content_hash' => sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()

	];
});

$factory->define(\App\VideoFile::class, function (Faker\Generator $faker) {
	static $password;

	return [
		'path'         => 'some/file/path',
		'content_hash' => sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(\App\ImageFile::class, function (Faker\Generator $faker) {
	static $password;

	return [
		'path'         => 'some/file/path',
		'content_hash' => sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});

$factory->define(\App\DocumentFile::class, function (Faker\Generator $faker) {
	static $password;

	return [
		'path'         => 'some/file/path',
		'content_hash' => sha1('secret'),
		'notes'        => $faker->sentences(3, TRUE),
		'is_public'    => $faker->boolean()
	];
});
