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

// VueJS Setup for history-Routing
Route::group(['prefix' => 'vue', 'as' => 'vue.'], function () {

	Route::get('{vue_capture?}', function () {
		return view('vuerouter.index');
	})->where('vue_capture', '[^<>]*')
		 ->middleware(['auth']);

});


// Admin Interface Routes
Route::group(['prefix' => 'pool', 'as' => 'pool.'], function () {

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
	Route::get('resource/{resource}/assign/pdf', 'PdfMaterialAssignmentController@index')
		 ->where('resource', '[0-9]+')
		 ->name('resource.assign.pdf.material');
	Route::get('resource/{resource}/download', 'ResourceController@download')
		 ->name('resource.download');
	Route::get('resource/{resource}/videostream', 'VideoStreamController@stream')
		 ->name('resource.videostream');
	Route::get('resource/{resource}/material/{material}/pdfdownload', 'PdfResourceController@downloadPages')
		 ->where('resource', '[0-9]+')
		 ->where('material', '[0-9]+')
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

Route::get('/resource/image/{resource}/{width?}/{height?}', 'ResourcePreviewController@getImage')
	 ->name('resource.image.preview')
	 ->where('width', '[0-9]+')
	 ->where('height', '[0-9]+');

Route::get('pdfpreview/res-{resource}/page-{page}', 'ResourcePreviewController@getPageImage')
	 ->where('resource', '[0-9]+')
	 ->where('page', '[0-9]+')
	 ->name('PdfPreview/ImagePreview')
	 ->middleware(\Spatie\ResponseCache\Middlewares\CacheResponse::class)
	 ->middleware(\App\Http\Middleware\CacheControlHeaders::class);

Route::get('/bibleverse/{from}-{to}', 'Api\BibleverseController@show')
	 ->name('bibleverse')
	 ->where('from', '[0-9]+')
	 ->where('to', '[0-9]+');


Auth::routes();

Route::get('/home', 'HomeController@index');
