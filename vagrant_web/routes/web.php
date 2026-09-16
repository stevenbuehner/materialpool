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

Route::get('/up', function () {
	event(new \Illuminate\Foundation\Events\DiagnosingHealth());

	return response('OK', 200)->header('Content-Type', 'text/plain');
})->name('health');

Route::get('/', function () {
	return redirect('/vue');
	// return view('welcome');
});

// VueJS Setup for history-Routing
Route::group(['prefix' => 'vue', 'as' => 'vue.'], function () {

	Route::get('{vue_capture?}', function () {
		return view('vuerouter.index');
	})->where('vue_capture', '[^<>]*')
		->middleware(['auth', 'active']);

});

Route::get('/home', 'HomeController@index')->middleware(['auth', 'active']);

Route::get('/keepalive', 'HomeController@keepalive')
	->name('token_keepalive')
	->middleware(['auth', 'active']);


// Admin Interface Routes
Route::group(['prefix' => 'pool', 'as' => 'pool.', 'middleware' => ['auth', 'active']], function () {

	// route name: "pool.material.index", ...
	Route::resource('material', 'MaterialController');


	Route::get('material/{material}/delete', 'MaterialController@delete')
		->where('material', '[0-9]+')
		->name('material.delete');

	Route::get('/keyword/{lcKeyword}', 'MaterialController@indexBySingleKeyword')
		->name('material.by.keyword');
	Route::get('/bibleverse/{from}-{to}', 'MaterialController@indexByBibleverse')
		->name('material.by.bibleverse')
		->where('from', '[0-9]+')
		->where('to', '[0-9]+');

	Route::resource('resource', 'ResourceController');
	Route::get('resource/{resource}/download', 'ResourceController@download')
		->name('resource.download');
	Route::get('resource/{resource}/mediastream', 'MediaStreamController@stream')
		->middleware('can:view,resource')
		->name('resource.mediastream');
	Route::get('resource/{resource}/material/{material}/pdfdownload', 'PdfResourceController@downloadPages')
		->where('resource', '[0-9]+')
		->where('material', '[0-9]+')
		->middleware(['can:view,resource', 'can:view,material'])
		->name('resource.limitedpdf.download');


	// Search
	Route::get('searchbar', 'SearchController@index')->name('searchbar.index');
	Route::get('search/guess', 'SearchController@guess')
		->name('searchbar.guess');
	Route::get('search/guess2', 'SearchController@guess2')
		->name('searchbar.guess2');
	Route::get('search/guess/keywords', 'SearchController@guessKeywords')
		->name('searchbar.guessKeywords');
	Route::post('search/guess/bibleverses', 'SearchController@guessBibleverse')
		->name('searchbar.guessBibleverses');
	Route::post('search/get', 'SearchController@get')
		->name('searchbar.get');

});

Route::get('/resource/{resource}/image/{width?}/{height?}', 'ResourcePreviewController@getImage')
	->where('width', '[0-9]+')
	->where('height', '[0-9]+')
	->middleware(['auth', 'active', 'can:view,resource'])
	->middleware(\App\Http\Middleware\CacheControlHeaders::class) // Tell browser to keep cache for one week
	->name('resource.image.preview');

Route::get('/resource/{resource}/image/page-{page}/{clearCache?}', 'ResourcePreviewController@getPageImage')
	->where('resource', '[0-9]+')
	->where('page', '[0-9]+')
	->where('clearCache', 'refresh')
	->name('PdfPreview/ImagePreview')
	->middleware(['auth', 'active', 'can:view,resource'])
	->middleware(\App\Http\Middleware\CacheControlHeaders::class); // Tell browser to keep cache for one week

Route::get('material/{material}/preview', 'MaterialPreviewController@getMaterialPreview')
	->where('material', '[0-9]+')
	->middleware(['auth', 'active', 'can:view,material'])
	->middleware(\App\Http\Middleware\CacheControlHeaders::class)// Tell browser to keep cache for one week
	->name('material.preview');


Route::get('/bibleverse/{from}-{to}', 'Api\BibleverseController@show')
	->where('from', '[0-9]+')
	->where('to', '[0-9]+')
	->middleware(['auth', 'active'])
	->name('bibleverse');


Auth::routes($options = ['register' => FALSE]);
