<?php
/**
 * This file was created by  steven
 * Created: 05.06.17 21:26
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\Processors\Exceptions;

use App\Models\Resource;
use Throwable;

class ResourceNotHashable extends \Exception {

	public function __construct(Resource $resource, $code = 0, ?Throwable $previous = NULL) {
		parent::__construct("This Resource (id: {$resource->id}) is not hashable", $code, $previous);
	}

}
