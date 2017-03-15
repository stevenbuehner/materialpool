<?php
/**
 * This file was created by  steven
 * Created: 15.03.17 20:55
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Http\View\Facades;


use Illuminate\Support\Facades\Facade;

class ResourceHelperFacade extends Facade {

	/**
	 * @see http://www.expertphp.in/article/how-to-create-custom-facade-in-laravel-52
	 */
	public static function getFacadeAccessor() {
		return 'ResourceHelper';
	}

}