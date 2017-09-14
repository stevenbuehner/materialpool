<?php

namespace App\Services\TagExtraction;

use App\Models\Material;
use App\Models\Resource;
use App\Services\TagExtraction\Interfaces\PropertyInterface;
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
	 * @param Resource|Resource[] $resources
	 * @param array               $additionalInformation
	 * @return Material
	 */
	public function createGuessedMaterialFromResource($resources, $additionalInformation = []) {

		$resources             = collect($resources);
		$additionalInformation = collect($additionalInformation);
		$properties            = new Collection();
		$material              = new Material();
		$material->from_bot    = TRUE;
		$material->created_by  = $resources->first()->created_by;
		$material->modified_by = $resources->first()->created_by;
		$material->title       = $resources->count() > 1 ? $resources->count() . ' Resources' : 'Default ' . $resources->first()->type . ' title';
		$material->save();
		$material->resources()->attach($resources->pluck('id'));


		// Extract properties from resources
		foreach ($resources as $resource) {
			$resProp    = $this->extractPropertiesFromResources($resource);
			$properties = $properties->merge($resProp);
		}


		// Extract properties from additionalInformation
		foreach ($additionalInformation->get('properties', []) as $keywordString) {
			$foundTags  = $this->tagExtractionService->extractPartsFromStrings($keywordString);
			$properties = $properties->merge($foundTags);
		}

		$this->insertPropertiesIntoMaterial($material, $properties)
			 ->save();

		return $material;
	}

	public function extractPropertiesFromResources(Resource $resource) {

		/** @var Resource $resource */
		$handlerColl = $resource->getTagExtractionClasses();
		$properties  = new Collection();

		foreach ($handlerColl as $handlerClass) {
			/** @var HandlerInterface $handler */
			$handler    = resolve($handlerClass);
			$properties = $properties->merge($handler->handle($resource));
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

		return $properties;
	}

	/**
	 * Orders, prioritizes and inserts properties into an existing material.
	 * But it does NOT save the material.
	 *
	 * @param Material                       $material
	 * @param PropertyInterface[]|Collection $properties
	 * @return Material
	 */
	public function insertPropertiesIntoMaterial(Material $material, $properties) {

		$properties = collect($properties);

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
			/** @var Collection $coll */
			$coll->first()->insertYourselfToItem($material);
		});

		$multiplePropertiesAllowed = $orderedProperties->diff($onlyOnePropertyAllowed->toArray());
		$multiplePropertiesAllowed->each(function (Property $property) use ($material) {
			$property->insertYourselfToItem($material);
		});

		// Force Reloading any Relationships the next time
		$material->setRelations([]);

		return $material;
	}
}

?>