<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class ResourceWasCreated extends ResourceWasChanged {
	use SerializesModels;

}
