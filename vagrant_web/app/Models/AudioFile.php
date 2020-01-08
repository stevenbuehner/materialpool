<?php

namespace App\Models;

use App\Models\Traits\TimeCountTrait;

class AudioFile extends File {

	use TimeCountTrait;

	protected static $singleTableType = 'audio';

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		$this->setupTimeCountAttribute();
	}

}
