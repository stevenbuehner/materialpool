<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class ResourceWasDeleted extends ResourceWasChanged {
	use SerializesModels;
}
