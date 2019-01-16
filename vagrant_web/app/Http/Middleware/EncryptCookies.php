<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;
use Symfony\Component\HttpFoundation\Request;

class EncryptCookies extends BaseEncrypter
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        //
    ];


	/**
	 * Indicates if the cookies should be serialized.
	 * Workaround until passport gets fixed: https://github.com/laravel/passport/issues/805
	 * @var bool
	 */
	// protected static $serialize = true;

}
