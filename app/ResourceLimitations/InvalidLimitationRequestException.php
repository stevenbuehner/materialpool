<?php
/**
 * This file was created by  steven
 * Created: 12.09.17 11:30
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\ResourceLimitations;


use Exception;
use Throwable;

class InvalidLimitationRequestException extends Exception {
	public function __construct($message = "", $code = 0, ?Throwable $previous = NULL) {
		parent::__construct($message, $code, $previous);
	}

}
