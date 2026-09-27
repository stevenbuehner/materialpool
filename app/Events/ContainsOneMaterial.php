<?php

namespace App\Events;

use App\Models\Material;

interface ContainsOneMaterial {

	public function getMaterial(): Material;

}