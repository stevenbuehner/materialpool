<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class CacheControlHeaders {
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure                 $next
	 * @return mixed
	 */
	public function handle($request, Closure $next, $lifetimeAge = 60 * 60 * 24 * 7) {

		/** @var Response $response */
		$response = $next($request);

		$maxAge = $lifetimeAge;

		$response->header('Expires', gmdate(DATE_RFC1123, time() + $maxAge));
		$response->setCache([
								'max_age' => $maxAge,
								'public'  => TRUE
							]);

		return $response;
	}
}
