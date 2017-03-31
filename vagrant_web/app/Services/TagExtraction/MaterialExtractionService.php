<?php

namespace App\Services\TagExtraction;

use App\Models\Resource;

class MaterialExtractionService {
	protected $tagExtractionService = NULL;

	public function __construct(TagExtractionService $tagExtractionService) {
		$this->tagExtractionService = $tagExtractionService;
	}

	public function createGuessedMaterialFromResource(Resource $resource) {

	}


}

?>