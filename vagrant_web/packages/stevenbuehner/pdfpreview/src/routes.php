<?php

Route::get('test/{resource}', 'StevenBuehner\PdfPreview\Controllers\TestController@index');

Route::get('pdfpreview/res-{fileId}/page-{page}/{width?}',
		   'StevenBuehner\PdfPreview\Controllers\PdfImagePreviewController@loadPage')
	 ->where('fileId', '[0-9]+')
	 ->where('page', '[0-9]+')
	 ->where('width', '[0-9]+')
	 ->name('PdfPreview/ImagePreview');