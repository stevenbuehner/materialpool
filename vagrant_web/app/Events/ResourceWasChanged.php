<?php

namespace App\Events;

use App\Models\Resource;
use Illuminate\Queue\SerializesModels;

class ResourceWasChanged implements ContainsOneResource {
	use SerializesModels;

	protected $resource;

	/**
	 * Create a new event instance.
	 *
	 * @param Resource $resource
	 */
	public function __construct(Resource $resource) {
		$this->resource = $resource;
	}

	/**
	 * @return Resource
	 */
	public function getResource(): Resource {
		return $this->resource;
	}
}
