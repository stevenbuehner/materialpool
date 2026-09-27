<?php

namespace App\Events;

use App\Models\Material;
use App\Models\Resource as ResourceEntity;
use Illuminate\Queue\SerializesModels;

class ResourceWasDetached implements ContainsOneResource, ContainsOneMaterial {
	use SerializesModels;

	protected $material;
	protected $resource;

	/**
	 * Create a new event instance.
	 *
	 * @param Material $material
	 * @param Resource $resource
	 */
	public function __construct(Material $material, ResourceEntity $resource) {
		$this->material = $material;
		$this->resource = $resource;
	}

	/**
	 * @return ResourceEntity
	 */
	public function getResource(): ResourceEntity {
		return $this->resource;
	}

	/**
	 * @return Material
	 */
	public function getMaterial(): Material {
		return $this->material;
	}
}
