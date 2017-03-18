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
	Route::get('{foreignInstance}/resources', 'ResourceController@index')->where('foreignInstance', '[0-9]+');

	Route::get('resources/{resource}', 'ResourceController@show')->where(['resource' => '[0-9]+']);
	Route::get('{foreignInstance}/resources/{resource}', 'ResourceController@showByRemoteId')
		 ->where(['foreignInstance', '[0-9]+', 'resource' => '[0-9]+']);

	Route::post('{foreignInstance}/resources/{type}', 'ResourceController@addByRemoteId')
		 ->where(['foreignInstance' => '[0-9]+', 'type' => join('|',
																array_keys(\App\Models\Resource::getSingleTableTypeMap()))]);


});
