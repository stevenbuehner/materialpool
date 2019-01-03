<?php

namespace App\Services\TagExtraction;

use App\Events\MaterialWasCreated;
use App\Events\ResourceWasAttached;
use App\Models\Material;
use App\Models\Resource;
use App\Services\TagExtraction\Interfaces\PropertyInterface;
use App\Services\TagExtraction\Interfaces\RelevanceInterface;
use App\Services\TagExtraction\Properties\BibleverseProperty;
use App\Services\TagExtraction\Properties\KeywordProperty;
use App\Services\TagExtraction\Properties\OcrTextProperty;
use App\Services\TagExtraction\Properties\Property;
use App\Services\TagExtraction\Properties\RatingProperty;
use App\Services\TagExtraction\Properties\TitleProperty;
use App\Services\TagExtraction\ResourceHandles\HandlerInterface;
use Illuminate\Support\Collection;
use StevenBuehner\BibleVerseBundle\Service\BibleVerseService;

class MaterialExtractionService {
	protected $tagExtractionService = NULL;
	protected $bibleVerseService    = NULL;

	public function __construct(TagExtractionService $tagExtractionService, BibleVerseService $bibleVerseService) {
		$this->tagExtractionService = $tagExtractionService;
		$this->bibleVerseService    = $bibleVerseService;
	}

	/**
	 * Helper function to compare two BibleVerseProperties objects with usort
	 *
	 * @param BibleverseProperty $v1
	 * @param BibleverseProperty $v2
	 * @return int
	 */
	public static function usortBibleVerseProperty(BibleverseProperty $v1, BibleverseProperty $v2) {
		return BibleVerseService::usortBibleverses($v1->getValue(), $v2->getValue());
	}

	/**
	 * @param Resource|Resource[] $resources
	 * @param array               $additionalInformation
	 * @return Material
	 */
	public function createGuessedMaterialFromResource($resources, $additionalInformation = []) {

		$resources             = ($resources instanceof Resource) ? collect([$resources]) : collect($resources);
		$additionalInformation = collect($additionalInformation);
		$properties            = new Collection();
		$material              = new Material();
		$material->from_bot    = TRUE;
		$material->created_by  = $resources->first()->created_by;
		$material->modified_by = $resources->first()->created_by;
		$material->title       = $resources->count() > 1 ? $resources->count() . ' Resources' : 'Default ' . $resources->first()->type . ' title';
		$material->save();
		$material->resources()->attach($resources->pluck('id'));

		// Add defaultproperties
		$properties = $properties->merge($this->getDefaultProperties());

		// Extract properties from resources
		foreach ($resources as $resource) {
			$resProp    = $this->extractPropertiesFromResources($resource);
			$properties = $properties->merge($resProp);
		}

		// Extract properties from additionalInformations metatext
		foreach ($additionalInformation->get('metatext', []) as $keywordString) {
			$foundTags = $this->tagExtractionService->extractPartsFromStrings($keywordString);

			// Über den 'metatext'-Kanal mitgelieferte Properties bekommen automatisch eine mittlere Relevanz
			// Wird genutzt z.B. beim Erstellen von TextRessourcen direkt aus dem Frontend
			$foundTags->each(function ($tag) {
				/** @var Property $tag */
				$tag->setRelevance(RelevanceInterface::RELEVANCE_USER_AVG);
			});

			$properties = $properties->merge($foundTags);
		}

		// Extract properties from additionalInformations properties
		foreach ($additionalInformation->get('properties', []) as $property) {
			if ($property instanceof PropertyInterface) {
				$properties = $properties->push($property);
			} else {
				// Log error?
			}
		}

		// Make shure we have every property only once!
		$properties = $properties->unique();

		$properties = $this->mergeBibleverseProperties($properties);

		// This is actually not needed
		// $properties = $this->sortBibleVerseProperties($properties);

		$this->insertPropertiesIntoMaterial($material, $properties)
			 ->save();

		event(new MaterialWasCreated($material));

		foreach ($material->resources as $resource) {
			event(new ResourceWasAttached($material, $resource));
		}

		return $material;
	}

	protected function getDefaultProperties() {
		return collect([new RatingProperty(10)]);
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
	 * @param Collection <Property> $properties
	 * @return Collection <Property>
	 */
	public function mergeBibleverseProperties($properties) {

		// Extract BibleverseProperties

		// Merge intersecting bibleverses
		$allBibleVerseProperties = new Collection();
		$resultProperties        = new Collection();
		$properties->each(function ($item) use ($allBibleVerseProperties, $resultProperties) {
			if ($item instanceof BibleverseProperty) {
				$allBibleVerseProperties->push($item);
			} else {
				$resultProperties->push($item);
			}
		});

		// Group BibleVerseProperties by Relevance
		// So only verses with the same relevance will be merged
		$biblVersePropertyGroups = $allBibleVerseProperties->groupBy(function (Property $item) {
			return $item->getRelevance();
		});

		$biblVersePropertyGroups->each(function (Collection $group, $relevance) use ($resultProperties) {
			$bibleVerses = $group->transform(function (BibleverseProperty $item) {
				return $item->getValue();
			});

			$merged = $this->bibleVerseService->mergeBibleverses($bibleVerses->toArray());

			foreach ($merged as $bibleVerse) {
				$resultProperties->push(new BibleverseProperty($bibleVerse, $relevance));
			}
		});

		// ToDo Elleminate Bibleverses of lower Relevance that exist in higher relevances ...

		return $resultProperties;
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

	/**
	 * @param Collection $properties
	 * @return Collection
	 */
	public function sortBibleVerseProperties($properties) {
		/** @var Collection $onlyBibleversProps */
		/** @var Collection $otherProps */
		list($onlyBibleversProps, $otherProps) = $properties->partition(function ($item) {
			return $item instanceof BibleverseProperty;
		});

		$onlyBibleversProps = $onlyBibleversProps->sort([self::class, 'usortBibleVerseProperty']);

		return $otherProps->merge($onlyBibleversProps);
	}

}

?>