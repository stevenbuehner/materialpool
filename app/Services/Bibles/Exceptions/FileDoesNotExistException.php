<?php

namespace App\Services\Bibles\Exceptions;

use Exception;
use Throwable;

class FileDoesNotExistException extends Exception {

	public function __construct(string $message = "", int $code = 0, ?Throwable $previous = NULL) {
		parent::__construct($message, $code, $previous);
	}

}
