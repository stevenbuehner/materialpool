<?php

namespace App\Services\TagExtraction;

use App\Models\Material;
use App\Models\Resource;
use App\Services\TagExtraction\Properties\BibleverseProperty;
use App\Services\TagExtraction\Properties\KeywordProperty;
use App\Services\TagExtraction\Properties\OcrTextProperty;
use App\Services\TagExtraction\Properties\Property;
use App\Services\TagExtraction\Properties\TitleProperty;
use App\Services\TagExtraction\ResourceHandles\HandlerInterface;
use Illuminate\Support\Collection;

class MaterialExtractionService {
	protected $tagExtractionService = NULL;

	public function __construct(TagExtractionService $tagExtractionService) {
		$this->tagExtractionService = $tagExtractionService;
	}

	/**
	 * @param Resource $resource
	 * @return Material
	 */
	public function createGuessedMaterialFromResource(Resource $resource, $additionalInformation = []) {

		/** @var Resource $resource */
		$additionalInformation = collect($additionalInformation);
		$handlerColl           = $resource->getTagExtractionClasses();
		$properties            = new Collection();
		$material              = new Material();
		$material->from_bot    = TRUE;
		$material->created_by  = $resource->created_by;
		$material->modified_by = $resource->created_by;
		$material->title       = 'Default ' . $resource->type . ' title';
		$material->save();
		$material->resources()->attach($resource);

		foreach ($handlerColl as $handlerClass) {
			/** @var HandlerInterface $handler */
			$handler    = resolve($handlerClass);
			$properties = $properties->merge($handler->handle($resource));
		}

		// Extract properties from additionalInformation
		foreach ($additionalInformation->get('properties', []) as $keywordString) {
			$foundTags  = $this->tagExtractionService->extractPartsFromStrings($keywordString);
			$properties = $properties->merge($foundTags);
		}

		// Create a TitleProperty if non exists from oxrText
		if ($properties->filter(function (Property $property) {
				return $property instanceof TitleProperty;
			})->count() == 0
		) {
			$ocrTextProperties = $properties->filter(function (Property $property) {
				return $property instanceof OcrTextProperty;
			});

			if ($ocrTextProperties->count()) {
				$defaultTitle = str_limit($ocrTextProperties->first()->getValue(), 60);
				$properties->push(new TitleProperty($defaultTitle, 0));
			}
		}

		// Order descending by Relevance
		$orderedProperties = $properties->sortByDesc(function (Property $property) {
			return $property->getRelevance();
		});

		$onlyOnePropertyAllowed = $orderedProperties->reject(function (Property $property) {
			return ($property instanceof KeywordProperty || $property instanceof BibleverseProperty);
		});

		$onlyOnePropertyAllowed->groupBy(function (Property $property) {
			return class_basename($property);
		})->each(function ($coll) use ($material) {
			$coll->first()->insertYourselfToItem($material);
		});

		$multiplePropertiesAllowed = $orderedProperties->diff($onlyOnePropertyAllowed->toArray());
		$multiplePropertiesAllowed->each(function (Property $property) use ($material) {
			$property->insertYourselfToItem($material);
		});

		// Force Reloading any Relationships the next time
		$material->setRelations([]);

		$material->save();


		return $material;
	}
}

?>