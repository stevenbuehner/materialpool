<?php

namespace Modules\MaterialGrabber\GrabberTemplates;


class WrongConstructorParameter extends \Exception {

	public function __construct($message = 'The constructor has wrong parameters') {
		parent::__construct($message);
	}
}