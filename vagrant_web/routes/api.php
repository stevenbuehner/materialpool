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

	Route::get('resources/{resource}', 'ResourceController@show')->where(['resource' => '[0-9]+']);


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


});
