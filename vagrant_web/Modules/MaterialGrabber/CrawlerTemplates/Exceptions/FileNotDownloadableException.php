<?php

namespace Modules\MaterialGrabber\CrawlerTemplates\Exceptions;

use Symfony\Component\Config\Definition\Exception\Exception;

class FileNotDownloadableException extends Exception {

	public function __construct($message = 'The given URL could not be downloaded propperly.') {
		parent::__construct($message);
	}
}