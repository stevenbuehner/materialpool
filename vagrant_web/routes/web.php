<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// use \Illuminate\Routing\Route;

Route::get('/', function () {
	return view('welcome');
});


// Admin Interface Routes
Route::group(['prefix' => 'pool', 'as' => 'pool.'], function () {

	// route name: "pool.material.index", ...
	Route::resource('material', 'MaterialController');

	Route::resource('resource', 'ResourceController', ['except' => ['store']]);

	Route::post('resource/file', 'ResourceController@storeFile')->name('resource.store.file');
	Route::post('resource/text', 'ResourceController@storeText')->name('resource.store.text');


});

Route::get('/keyword/{keyword}', 'KeywordController@show')
	 ->name('keyword');


Route::get('/bibleverse/{from}-{to}', 'BibleVerseController@show')
	 ->name('bibleverse')
	 ->where('from', '[0-9]+')
	 ->where('to', '[0-9]+');


// Admin Interface Routes
Route::group(['prefix'     => config('backpack.base.route_prefix', 'admin'),
			  'middleware' => ['admin'],
			  'namespace'  => 'Admin'], function () {

	// Backpack\CRUD: Define the resources for the entities you want to CRUD.
	CRUD::resource('keyword', 'KeywordCrudController');
	CRUD::resource('material', 'MaterialCrudController');

	// [...] other routes
});

Auth::routes();

Route::get('/home', 'HomeController@index');

Auth::routes();

Route::get('/home', 'HomeController@index');
