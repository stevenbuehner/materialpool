<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Admin {
	/**
	 * Handle an incoming request.
	 *
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 */
	public function handle($request, Closure $next, $guard = NULL) {

		if (request()->user()->isSuperAdmin() === TRUE) {
			return $next($request);
		} else {
			return response(['error' => 'Only admins are allowed!'])->setStatusCode(404);
		}
	}
}
