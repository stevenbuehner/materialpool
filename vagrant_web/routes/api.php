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

use Illuminate\Support\Facades\Route;

Route::group([
				 'middleware' => 'auth:api',
				 'prefix'     => 'v2',
				 'namespace'  => 'Api',
				 'as'         => 'api.v2.'
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
				 'namespace' => 'Api',
				 'as'        => 'api.v1.'
			 ], function () {


	// General
	Route::get('general/options', 'GeneralOptionsController@index')
		 ->name('general.options');

	// Resources
	Route::get('resources/find', 'ResourceController@find')
		 ->name('resources.find');


	// Materials
	Route::get('materials', 'MaterialController@index')
		 ->name('materials.index');
	Route::get('materials/{material}', 'MaterialController@show')
		 ->where(['material' => '[0-9]+'])
		 ->name('materials.show');
	Route::post('materials', 'MaterialController@store')
		 ->name('materials.store');
	Route::put('materials/{material}', 'MaterialController@update')
		 ->where(['material' => '[0-9]+'])
		 ->name('materials.update');
	Route::put('materials/{material}/resources', 'MaterialController@associateResources')
		 ->where(['material' => '[0-9]+'])
		 ->name('materials.associateResources');
	Route::get('materials/{material}/copy', 'MaterialController@copy')
		 ->where(['material' => '[0-9]+'])
		 ->name('materials.show');
	Route::get('materials/{material}/create-download', 'MaterialController@createPublicZipDownload')
		 ->where(['material' => '[0-9]+'])
		 ->name('materials.createPublicZipDownload');

	// Bibleverses
	Route::get('bibleverses', 'BibleverseController@index')
		 ->name('bibleverses.index');
	Route::get('bibleverses/{bibleverse}', 'BibleverseController@show')
		 ->name('bibleverses.show');
	Route::post('bibleverses', 'BibleverseController@store')
		 ->name('bibleverses.store');


	// Keywords
	Route::get('keywords', 'KeywordController@index')
		 ->name('keywords.index');
	Route::get('keywords/{keyword}', 'KeywordController@show')
		 ->where(['keyword' => '[0-9]+'])
		 ->name('keywords.show');
	Route::post('keywords', 'KeywordController@create')
		 ->name('keywords.create');
	Route::put('keywords/{keyword}', 'KeywordController@update')
		 ->where(['keyword' => '[0-9]+'])
		 ->name('keywords.update');
	Route::delete('keywords/{keyword}', 'KeywordController@delete')
		 ->where(['keyword' => '[0-9]+'])
		 ->name('keywords.delete');

	// Material <- Keywords-Relevance
	Route::put('material/{material}/keyword/{keyword?}', 'KeywordController@createOrUpdateAssignment')
		 ->where(['material' => '[0-9]+'])
		 ->where(['keyword' => '[0-9]+'])
		 ->name('keywords.updateAssignment');
	Route::delete('material/{material}/keyword/{keyword}', 'KeywordController@deleteAssignment')
		 ->where(['material' => '[0-9]+'])
		 ->where(['keyword' => '[0-9]+'])
		 ->name('keywords.deleteAssignment');

	// Material <- Bibleverse-Relevance
	Route::put('material/{material}/bibleverse/{bibleverse?}', 'BibleverseController@createOrUpdateAssignment')
		 ->name('bibleverses.createOrUpdateAssignment');
	Route::delete('material/{material}/bibleverse/{bibleverse}', 'BibleverseController@deleteAssignment')
		 ->name('bibleverses.deleteAssignment');

	/*
	 * Aus der Sicht der Foreign Instance mit ihren eigenen IDs
	 */

	// Neu - Foreign-Material
	Route::get('foreign-materials/{foreignMaterialId}', 'ForeignMaterialController@show')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:view,foreignMaterialId')
		 ->name('foreignMaterialShow');
	Route::post('foreign-materials/{foreignMaterialId}', 'ForeignMaterialController@store')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:create,App\Models\ForeignMaterialId')
		 ->name('foreignMaterialStore');
	Route::put('foreign-materials/{foreignMaterialId}', 'ForeignMaterialController@update')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:update,foreignMaterialId')
		 ->name('foreignMaterialUpdate');
	Route::delete('foreign-materials/{foreignMaterialId}', 'ForeignMaterialController@destroy')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:delete,foreignMaterialId')
		 ->name('foreignMaterialDelete');

	Route::post('foreign-materials/{foreignMaterialId}/create-from-resource',
				'ForeignMaterialController@createFromResources')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:create,App\Models\ForeignMaterialId')
		 ->name('foreignMaterialCreateFromResource');

	// Neu - Ressourcen
	Route::get('resources/{resource}', 'ResourceController@show')
		 ->where('resource', '[0-9]+')
		 ->middleware('can:view,resource')
		 ->name('resources.show');
	Route::get('foreign-resources/{foreignResourceId}', 'ForeignResourceController@showForeign')
		 ->where('foreignResourceId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:view,foreignResourceId')
		 ->name('foreignResources.show');

	Route::post('resources/', 'ResourceController@store')
		 ->middleware('can:create,App\Models\Resource')
		 ->name('resources.store');
	Route::post('foreign-resources/', 'ForeignResourceController@storeForeign')
		 ->middleware('can:create,App\Models\ForeignResourceId')
		 ->name('foreignResources.store');
	Route::post('resources/create-material', 'ResourceController@createMaterialFromResourceIds')
		 ->middleware('can:create,App\Models\Material')
		 ->name('resources.create-material');

	Route::post('resources/{resource}/pdf-tags', 'PdfTagExtractionController@extractTags')
		 ->where('resource', '[0-9]+')
		 ->middleware('can:view,resource')
		 ->name('resources.pdf-tags');


	Route::put('resources/{resource}', 'ResourceController@update')
		 ->where('resource', '[0-9]+')
		 ->middleware('can:update,resource')
		 ->name('resources.update');
	Route::put('foreign-resources/{foreignResourceId}', 'ForeignResourceController@updateForeign')
		 ->where('foreignResourceId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:update,foreignResourceId')
		 ->name('foreignResources.update');

	Route::delete('resources/{resource}', 'ResourceController@destroy')
		 ->where('resource', '[0-9]+')
		 ->middleware('can:delete,resource')
		 ->name('resources.delete');
	Route::delete('foreign-resources/{foreignResourceId}', 'ForeignResourceController@destroyForeign')
		 ->where('foreignResourceId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:delete,foreignResourceId')
		 ->name('foreignResources.delete');


	// Neu Attach/Detach Foreign-Resources + Foreign-Materials
	Route::post('foreign-material/{foreignMaterialId}/foreign-resource/{foreignResourceId}',
				'ForeignResourceMaterialController@attach')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->where('foreignResourceId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:update,foreignMaterialId')
		 ->middleware('can:view,foreignResourceId')
		 ->name('materialresource.attach');
	Route::delete('foreign-material/{foreignMaterialId}/foreign-resource/{foreignResourceId}',
				  'ForeignResourceMaterialController@detach')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->where('foreignResourceId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:update,foreignMaterialId')
		// ->middleware('can:view,foreignResourceId') // Even if the resource owner made his resource not public anymore, the detaching should work
		 ->name('materialresource.detach');
	Route::post('foreign-material/{foreignMaterialId}/sync', 'ForeignResourceMaterialController@sync')
		 ->where('foreignMaterialId', '[0-9a-zA-Z_-]+')
		 ->middleware('can:update,foreignMaterialId')
		 ->name('materialresource.sync');


	// Bundle import and update
	Route::get('bundles', 'BundleImportController@index')
		 ->name('bundles.show');
	Route::get('bundles/{bundle}', 'BundleImportController@show')
		 ->where('bundle', '[0-9]+')
		 ->name('bundles.show');
	Route::post('bundles/{bundle}/init-update', 'BundleImportController@initUpdate')
		 ->name('bundles.update.init')
		 ->where('bundle', '[0-9]+');
	Route::post('bundles/{bundle}/run-update', 'BundleImportController@runJobs')
		 ->name('bundles.update.run')
		 ->where('bundle', '[0-9]+');
	Route::get('bundles/{bundle}/icon', 'BundleImportController@getBundleIcon')
		 ->where('bundle', '[0-9]+')
		 ->name('bundles.geticon');

	// Todo: Create middleware can:....
	Route::get('biblecontents/{from}-{to}/{bibleUid?}', 'BibleContentController@getBibleverse')
		 ->where('from', '[0-9]{6,9}')
		 ->where('to', '[0-9]{6,9}')
		 ->where('bibleUid', '[a-zA-Z0-9_-]+')
		 ->name('biblecontents.get');
	Route::get('biblecontents/search/{bibleUid?}', 'BibleContentController@searchAndGet')
		 ->where('bibleUid', '[0-9a-zA-Z]+')
		 ->name('biblecontents.searchAndGet');

	Route::apiResource('bibles', 'BibleController')
		 ->only(['index', 'show']);

});
