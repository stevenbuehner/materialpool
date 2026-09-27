<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:26
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\PreviewGeneration\Exceptions;

use Exception;
use Throwable;

class NotPreviewAbleException extends Exception {

	public function __construct($message = "No Preview can be created from this", $code = 0, ?Throwable $previous = NULL) {
		parent::__construct($message, $code, $previous);
	}

}
