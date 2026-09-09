<?php

namespace App\Models;

use App\Services\Processors\ContentHashProviderInterface;
use Parental\HasParent;

class Book extends Resource implements ContentHashProviderInterface {
	use HasParent;

	protected static $singleTableType = 'book';

	/**
	 * @return string
	 */
	public function getContentsForHash() {
		return $this->options;
	}
}
