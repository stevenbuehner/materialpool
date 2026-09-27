<?php

namespace App\Events;

use App\Models\Resource;
use Illuminate\Queue\SerializesModels;

class ResourceWasDeleted extends ResourceWasChanged {
	use SerializesModels;

}
