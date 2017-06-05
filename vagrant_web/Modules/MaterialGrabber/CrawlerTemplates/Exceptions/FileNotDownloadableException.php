<?php

namespace Modules\MaterialGrabber\CrawlerTemplates\Exceptions;

class FileNotDownloadableException extends \Exception {

	public function __construct($message = 'The given URL could not be downloaded propperly.') {
		parent::__construct($message);
	}
}