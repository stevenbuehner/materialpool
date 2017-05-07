<?php

namespace Modules\MaterialGrabber\GrabberTemplates;

use Symfony\Component\Config\Definition\Exception\Exception;

class ConfigParameterDoesNotExistException extends Exception {

	public function __construct($message = 'The requested config-parameter does not exist.') {
		parent::__construct($message);
	}
}