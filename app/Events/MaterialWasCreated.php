<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class MaterialWasCreated extends MaterialWasChanged {
	use SerializesModels;
}
