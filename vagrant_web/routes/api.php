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
				 'middleware' => 'auth:api',
				 'prefix'     => 'v2',
				 'namespace'  => 'Api'
			 ], function () {


	// Neu: Attach/Detach Resources + Materials
	Route::post('material/{material}/resource/{resource}/attach',
				'ResourceMaterialController@attach')
		 ->where('material', '[0-9]+')
		 ->where('resource', '[0-9]+')
		 ->middleware('can:update,material')
		 ->middleware('can:view,resource')
		 ->name('api.v2.materialresource.attach');

	Route::delete('material/{material}/resource/{resource}/detach',
				  'ResourceMaterialController@detach')
		 ->where('material', '[0-9]+')
		 ->where('resource', '[0-9]+')
		 ->middleware('can:update,material')
		// ->middleware('can:view,resource') // Even if the resource owner made his resource not public anymore, the detaching should work
		 ->name('api.v2.materialresource.detach');

	Route::post('material/{material}/sync', 'ResourceMaterialController@sync')
		 ->where('material', '[0-9]+')
		 ->middleware('can:update,material')
		 ->name('api.v2.materialresources.sync');


	// Neu: Material
	Route::delete('materials/{material}',
				  'MaterialController@destroy')
		 ->where('material', '[0-9]+')
		 ->middleware('can:delete,material')
		// ->middleware('can:view,resource') // Even if the resource owner made his resource not public anymore, the detaching should work
		 ->name('api.v2.material.delete');


});


Route::group([
				 // 'middleware' => 'auth:api', // im Konstruktor der Klassen eingebettet
				 'prefix'    => 'v1',
				 'namespace' => 'Api'
			 ], function () {


	// Resources
	Route::get('resources/find', 'ResourceController@find')
		 ->name('api.v1.resources.find');


	// Materials
	Route::get('materials', 'MaterialController@index')
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
	Route::get('bibleverses/{bibleverse}', 'BibleverseController@show')
		 ->name('api.v1.bibleverses.show');
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
	Route::put('keywords/{keyword}', 'KeywordController@update')
		 ->where(['keyword' => '[0-9]+'])
		 ->name('api.v1.keywords.update');


	// Material <- Keywords-Relevance
	Route::put('material/{material}/keyword/{keyword?}', 'KeywordController@createOrUpdateAssignment')
		 ->where(['material' => '[0-9]+'])
		 ->where(['keyword' => '[0-9]+'])
		 ->name('api.v1.keywords.updateAssignment');
	Route::delete('material/{material}/keyword/{keyword}', 'KeywordController@deleteAssignment')
		 ->where(['material' => '[0-9]+'])
		 ->where(['keyword' => '[0-9]+'])
		 ->name('api.v1.keywords.deleteAssignment');

	// Material <- Bibleverse-Relevance
	Route::put('material/{material}/bibleverse/{bibleverse?}', 'BibleverseController@createOrUpdateAssignment')
		 ->name('api.v1.bibleverses.createOrUpdateAssignment');
	Route::delete('material/{material}/bibleverse/{bibleverse}', 'BibleverseController@deleteAssignment')
		 ->name('api.v1.bibleverses.deleteAssignment');

	/*
	 * Aus der Sicht der Foreign Instance mit ihren eigenen IDs
	 */

	// Neu - Foreign-Material
	Route::get('foreign-materials/{foreignMaterialId}', 'ForeignMaterialController@show')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:view,foreignMaterialId')
		 ->name('api.v1.foreignMaterialShow');
	Route::post('foreign-materials/{foreignMaterialId}', 'ForeignMaterialController@store')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:create,App\Models\ForeignMaterialId')
		 ->name('api.v1.foreignMaterialStore');
	Route::put('foreign-materials/{foreignMaterialId}', 'ForeignMaterialController@update')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:update,foreignMaterialId')
		 ->name('api.v1.foreignMaterialUpdate');
	Route::delete('foreign-materials/{foreignMaterialId}', 'ForeignMaterialController@destroy')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:delete,foreignMaterialId')
		 ->name('api.v1.foreignMaterialDelete');

	Route::post('foreign-materials/{foreignMaterialId}/create-from-resource',
				'ForeignMaterialController@createFromResources')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:create,App\Models\ForeignMaterialId')
		 ->name('api.v1.foreignMaterialCreateFromResource');

	// Neu - Ressourcen
	Route::get('resources/{resource}', 'ResourceController@show')
		 ->where('resource', '[0-9]+')
		 ->middleware('can:view,resource')
		 ->name('api.v1.resources.show');
	Route::get('foreign-resources/{foreignResourceId}', 'ForeignResourceController@showForeign')
		 ->where('foreignResourceId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:view,foreignResourceId')
		 ->name('api.v1.foreignResources.show');

	Route::post('resources/', 'ResourceController@store')
		 ->middleware('can:create,App\Models\Resource')
		 ->name('api.v1.resources.store');
	Route::post('foreign-resources/', 'ForeignResourceController@storeForeign')
		 ->middleware('can:create,App\Models\ForeignResourceId')
		 ->name('api.v1.foreignResources.store');

	Route::put('resources/{resource}', 'ResourceController@update')
		 ->where('resource', '[0-9]+')
		 ->middleware('can:update,resource')
		 ->name('api.v1.resources.update');
	Route::put('foreign-resources/{foreignResourceId}', 'ForeignResourceController@updateForeign')
		 ->where('foreignResourceId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:update,foreignResourceId')
		 ->name('api.v1.foreignResources.update');

	Route::delete('resources/{resource}', 'ResourceController@destroy')
		 ->where('resource', '[0-9]+')
		 ->middleware('can:delete,resource')
		 ->name('api.v1.resources.delete');
	Route::delete('foreign-resources/{foreignResourceId}', 'ForeignResourceController@destroyForeign')
		 ->where('foreignResourceId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:delete,foreignResourceId')
		 ->name('api.v1.foreignResources.delete');


	// Neu Attach/Detach Foreign-Resources + Foreign-Materials
	Route::post('foreign-material/{foreignMaterialId}/foreign-resource/{foreignResourceId}',
				'ForeignResourceMaterialController@attach')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->where('foreignResourceId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:update,foreignMaterialId')
		 ->middleware('can:view,foreignResourceId')
		 ->name('api.v1.materialresource.attach');
	Route::delete('foreign-material/{foreignMaterialId}/foreign-resource/{foreignResourceId}',
				  'ForeignResourceMaterialController@detach')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->where('foreignResourceId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:update,foreignMaterialId')
		// ->middleware('can:view,foreignResourceId') // Even if the resource owner made his resource not public anymore, the detaching should work
		 ->name('api.v1.materialresource.detach');
	Route::post('foreign-material/{foreignMaterialId}/sync', 'ForeignResourceMaterialController@sync')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:update,foreignMaterialId')
		 ->name('api.v1.materialresource.sync');
});
