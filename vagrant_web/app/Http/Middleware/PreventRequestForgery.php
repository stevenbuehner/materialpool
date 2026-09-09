<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

class PreventRequestForgery extends Middleware {
	/**
	 * Indicates whether the XSRF-TOKEN cookie should be set on the response.
	 */
	protected $addHttpCookie = TRUE;

	/**
	 * The URIs that should be excluded from request-forgery verification.
	 *
	 * @var array<int, string>
	 */
	protected $except = [
		//
	];
}
