<?php

namespace App\Events;

use App\Models\Material;
use Illuminate\Queue\SerializesModels;

class MaterialWasChanged implements ContainsOneMaterial {
	use SerializesModels;

	protected $material;

	/**
	 * Create a new event instance.
	 *
	 * @param Material $material
	 */
	public function __construct(Material $material) {
		$this->material = $material;
	}


	public function getMaterial(): Material {
		return $this->material;
	}
}
