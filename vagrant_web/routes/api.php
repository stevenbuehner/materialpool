<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
				 // 'middleware' => 'auth:api',
				 'prefix'    => 'v1',
				 'namespace' => 'Api'
			 ], function () {


	// Resources
	Route::get('resources/{resource}', 'ResourceController@show')
		 ->where(['resource' => '[0-9]+'])
		 ->name('api.v1.resources.show');
	Route::get('resources/find', 'ResourceController@find')
		 ->name('api.v1.resources.find');
	Route::put('resources/{resource}', 'ResourceController@update')
		 ->where(['resource' => '[0-9]+'])
		 ->name('api.v1.resources.update');
	Route::post('resources/', 'ResourceController@store')
		 ->name('api.v1.resources.store');

	// Materials
	Route::get('materials', 'MaterialController@index')
		 ->where(['material' => '[0-9]+'])
		 ->name('api.v1.materials.index');
	Route::get('materials/{material}', 'MaterialController@show')
		 ->where(['material' => '[0-9]+'])
		 ->name('api.v1.materials.show');
	Route::post('materials', 'MaterialController@store')
		 ->name('api.v1.materials.store');
	Route::put('materials/{material}', 'MaterialController@update')
		 ->where(['material' => '[0-9]+'])
		 ->name('api.v1.materials.update');
	Route::put('materials/{material}/resources', 'MaterialController@associateResources')
		 ->where(['material' => '[0-9]+'])
		 ->name('api.v1.materials.associateResources');

	// Bibleverses
	Route::get('bibleverses', 'BibleverseController@index')
		 ->name('api.v1.bibleverses.index');
	Route::post('bibleverses', 'BibleverseController@store')
		 ->name('api.v1.bibleverses.store');


	// Keywords
	Route::get('keywords', 'KeywordController@index')
		 ->name('api.v1.keywords.index');
	Route::get('keywords/{keyword}', 'KeywordController@show')
		 ->where(['keyword' => '[0-9]+'])
		 ->name('api.v1.keywords.show');
	Route::post('keywords', 'KeywordController@create')
		 ->name('api.v1.keywords.create');


	// Material <- Keywords-Relevance
	Route::put('material/{material}/keyword/{keyword?}', 'KeywordController@createOrUpdateAssignment')
		 ->name('api.v1.keywords.updateAssignment');
	Route::delete('material/{material}/keyword/{keyword}', 'KeywordController@deleteAssignment')
		 ->name('api.v1.keywords.deleteAssignment');

	// Material <- Bibleverse-Relevance
	Route::put('material/{material}/bibleverse/{bibleverse?}', 'BibleverseController@createOrUpdateAssignment')
		 ->name('api.v1.bibleverses.createOrUpdateAssignment');
	Route::delete('material/{material}/bibleverse/{bibleverse}', 'BibleverseController@deleteAssignment')
		 ->name('api.v1.bibleverses.deleteAssignment');

	/*
	 * Aus der Sicht der Foreign Instance mit ihren eigenen IDs
	 */

	// Resources
	Route::get('{foreignInstance}/resources', 'ForeignInstanceResourceController@index')
		 ->where('foreignInstance', '[0-9]+');
	Route::get('{foreignInstanceId}/resources/{remoteId}', 'ForeignInstanceResourceController@show')
		 ->where(['foreignInstanceId', '[0-9]+', 'remoteId' => '[0-9]+']);
	Route::post('{foreignInstance}/resources/{type}', 'ForeignInstanceResourceController@store')
		 ->where(['foreignInstance' => '[0-9]+', 'type' => join('|',
																array_keys(\App\Models\Resource::getSingleTableTypeMap()))]);
	Route::delete('{foreignInstanceId}/resource/{remoteResourceId}', 'ForeignInstanceResourceController@destroy')
		 ->where(['foreignInstanceId', '[0-9]+', 'remoteResourceId' => '[0-9]+'])
		 ->name('foreignInstanceResourceDelete');


	// ForeignInstance + Resource => Material + Keywords
	Route::get('{foreignInstance}/materials', 'ForeignInstanceMaterialController@index')
		 ->where('foreignInstance', '[0-9]+')
		 ->name('foreignInstanceMaterialIndex');

	Route::post('{foreignInstance}/resource/{remoteResourceId}/materials', 'ForeignInstanceMaterialController@store')
		 ->where(['foreignInstance' => '[0-9]+', 'remoteResourceId' => '[0-9]+'])
		 ->name('foreignInstanceMaterialStore');

});
