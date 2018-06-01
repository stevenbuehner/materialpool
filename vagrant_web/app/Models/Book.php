<?php

namespace App\Models;

use App\Services\Processors\ContentHashProviderInterface;

class Book extends Resource implements ContentHashProviderInterface {

	protected static $singleTableType = 'book';

	/**
	 * @return string
	 */
	public function getContentsForHash() {
		return $this->options;
	}
}
