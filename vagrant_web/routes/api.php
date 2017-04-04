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

	// Bibleverses
	Route::get('bibleverses/guess', 'BibleverseController@guess')
		 ->name('api.v1.bibleverses.guess');


	// Resources
	Route::get('resources/{resource}', 'ResourceController@show')
		 ->where(['resource' => '[0-9]+']);


	// Keywords
	Route::get('keywords', 'KeywordController@index')
		 ->name('api.v1.keywords.index');
	Route::get('keywords/{keyword}', 'KeywordController@show')
		 ->where(['keyword' => '[0-9]+'])
		 ->name('api.v1.keywords.show');


	// Material <- Keywords-Relevance
	Route::post('material/{material}/keyword/{keyword}', 'KeywordController@createAssignment')
		 ->name('api.v1.keywords.createAssignment');
	Route::put('material/{material}/keyword/{keyword}', 'KeywordController@updateAssignment')
		->name('api.v1.keywords.updateAssignment');
	Route::delete('material/{material}/keyword/{keyword}', 'KeywordController@deleteAssignment')
		 ->name('api.v1.keywords.deleteAssignment');

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
