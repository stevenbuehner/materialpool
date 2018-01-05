<?php
/**
 * This file was created by  steven
 * Created: 04.01.18 19:35
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Http\Facades;


use Illuminate\Support\Facades\Facade;

class ResourcePreviewHelperFacade extends Facade {

	protected static function getFacadeAccessor() {
		return 'ResourcePreview';
	}
}