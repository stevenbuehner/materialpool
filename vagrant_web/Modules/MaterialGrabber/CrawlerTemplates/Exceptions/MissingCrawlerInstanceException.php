<?php

namespace Modules\MaterialGrabber\CrawlerTemplates\Exceptions;

use Symfony\Component\Config\Definition\Exception\Exception;

class MissingCrawlerInstanceException extends Exception {

	public function __construct($message = 'Expected a valid crawler stored in this link.') {
		parent::__construct($message);
	}
}