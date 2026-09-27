<?php

namespace App\Services\Processors;

interface ContentHashProviderInterface {

	/**
	 * @return string
	 */
	public function getContentsForHash();

}