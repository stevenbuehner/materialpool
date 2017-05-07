<?php

namespace Modules\MaterialGrabber\GrabberTemplates\Exceptions;

use Symfony\Component\Config\Definition\Exception\Exception;

class MissingBundleSetupInterfaceException extends Exception {

	public function __construct($message = 'Missing the BundleSetupInterface in the given class.') {
		parent::__construct($message);
	}
}