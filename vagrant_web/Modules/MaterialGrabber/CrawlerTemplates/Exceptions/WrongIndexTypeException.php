<?php

namespace Modules\MaterialGrabber\CrawlerTemplates\Exceptions;

use Symfony\Component\Config\Definition\Exception\Exception;

class WrongIndexTypeException extends Exception {

	public function __construct($message = 'The given Link-Entity does not have the expected Index-Type.') {
		parent::__construct($message);
	}
}