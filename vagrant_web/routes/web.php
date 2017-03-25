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


Route::get('/resource', 'ResourceController@index');

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

	// [...] other routes
});
