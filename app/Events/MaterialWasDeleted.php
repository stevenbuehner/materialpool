<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class MaterialWasDeleted extends MaterialWasChanged {
	use SerializesModels;

}
