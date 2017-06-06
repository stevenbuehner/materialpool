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
	Route::get('/keyword/{lcKeyword}', 'MaterialController@indexBySingleKeyword')
		 ->name('material.by.keyword');
	Route::get('/bibleverse/{from}-{to}', 'MaterialController@indexByBibleverse')
		 ->name('material.by.bibleverse')
		 ->where('from', '[0-9]+')
		 ->where('to', '[0-9]+');

	Route::resource('resource', 'ResourceController', ['except' => ['store']]);
	Route::get('resource/{resource}/download', 'ResourceController@download')
		 ->name('resource.download');

	Route::post('resource/file', 'ResourceController@storeFile')->name('resource.store.file');
	Route::post('resource/text', 'ResourceController@storeText')->name('resource.store.text');

	// Search
	Route::get('searchbar', 'SearchController@index')->name('searchbar.index');
	Route::get('search/guess', 'SearchController@guess')
		 ->name('searchbar.guess');
	Route::post('search/get', 'SearchController@get')
		 ->name('searchbar.get');

});

Route::get('/resource/image/{resource}/{width?}/{height?}', 'ResourcePreviewController@getImage')
	 ->name('resource.image.preview')
	 ->where('width', '[0-9]+')
	 ->where('height', '[0-9]+');


Route::get('/bibleverse/{from}-{to}', 'Api\BibleverseController@show')
	 ->name('bibleverse')
	 ->where('from', '[0-9]+')
	 ->where('to', '[0-9]+');


// Admin Interface Routes
/*
Route::group(['prefix'     => config('backpack.base.route_prefix', 'admin'),
			  'middleware' => ['admin'],
			  'namespace'  => 'Admin'], function () {

	// Backpack\CRUD: Define the resources for the entities you want to CRUD.
	CRUD::resource('keyword', 'KeywordCrudController');
	CRUD::resource('material', 'MaterialCrudController');

	// [...] other routes
});
*/

Auth::routes();

Route::get('/home', 'HomeController@index');
