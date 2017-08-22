<?php

namespace StevenBuehner\PdfPreview\Controllers;

use App\Http\Controllers\Controller;

class TestController extends Controller {
	//

	public function index($resource) {
		//	echo "This is the test: " . $test;

		return view('PdfPreview::preview', [
			'fileId'            => $resource,
			'imagePreviewRoute' => 'PdfPreview/ImagePreview',
			'startPage'         => 1,
			'endPage'           => 5,
			'selectableRange'   => -1,
		]);
	}
}
