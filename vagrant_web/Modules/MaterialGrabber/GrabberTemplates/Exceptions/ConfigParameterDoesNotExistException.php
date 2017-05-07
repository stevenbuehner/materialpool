<?php

namespace Modules\MaterialGrabber\GrabberTemplates;

class ConfigParameterDoesNotExistException extends \Exception {

	public function __construct($message = 'The requested config-parameter does not exist.') {
		parent::__construct($message);
	}
}