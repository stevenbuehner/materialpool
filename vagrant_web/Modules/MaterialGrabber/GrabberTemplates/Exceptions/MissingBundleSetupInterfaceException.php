<?php

namespace Modules\MaterialGrabber\GrabberTemplates\Exceptions;

class MissingBundleSetupInterfaceException extends \Exception {

	public function __construct($message = 'Missing the BundleSetupInterface in the given class.') {
		parent::__construct($message);
	}
}