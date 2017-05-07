<?php

namespace Modules\MaterialGrabber\GrabberTemplates;

use Symfony\Component\Config\Definition\Exception\Exception;

class WrongConstructorParameter extends Exception {

	public function __construct($message = 'The constructor has wrong parameters') {
		parent::__construct($message);
	}
}