<?php

namespace App\Events;

use App\Models\Resource as ResourceEntity;

interface ContainsOneResource {

	public function getResource(): ResourceEntity;

}