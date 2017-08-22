<?php

Route::get('test/{resource}', 'StevenBuehner\PdfPreview\Controllers\ListingController@index');

Route::get('pdfpreview/res-{fileId}/page-{page}',
		   'StevenBuehner\PdfPreview\Controllers\PdfImagePreviewController@loadPage')
	 ->where('fileId', '[0-9]+')
	 ->where('page', '[0-9]+')
	 ->name('PdfPreview/ImagePreview')
	 ->middleware(\Spatie\ResponseCache\Middlewares\CacheResponse::class);