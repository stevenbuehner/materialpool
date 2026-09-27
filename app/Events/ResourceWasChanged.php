<?php

namespace App\Events;

use App\Models\Resource as ResourceEntity;
use Illuminate\Queue\SerializesModels;

class ResourceWasChanged implements ContainsOneResource {
	use SerializesModels;

	protected $resource;

	/**
	 * Create a new event instance.
	 *
	 * @param ResourceEntity $resource
	 */
	public function __construct(ResourceEntity $resource) {
		$this->resource = $resource;
	}

	/**
	 * @return ResourceEntity
	 */
	public function getResource(): ResourceEntity {
		return $this->resource;
	}
}
