<?php

namespace App\Jobs\Bundle;

use App\Exceptions\InvalidResourceTypeException;
use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\Keyword;
use App\Models\Material;
use App\Models\Person;
use App\Services\Bundles\BundlesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class InsertOrUpdateMaterial implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


	/** @var  Bundle $bundle */
	protected $bundle;

	protected $localMatInfo;

	/**
	 * InsertOrUpdateResource constructor.
	 *
	 * @param Bundle $bundle
	 * @param        $localFileInfo
	 */
	public function __construct(Bundle $bundle, $localMaterialInfo) {
		$this->bundle       = $bundle;
		$this->localMatInfo = $localMaterialInfo;
	}

	/**
	 * Execute the job.
	 *
	 */
	public function handle(BundlesService $bundlesService) {

		/** @var ForeignMaterialId $foreignMat */
		$foreignMat = ForeignMaterialId::where(['foreign_id' => $this->getUUID()])
									   ->with(['material', 'material.keywords', 'material.bibleverses'])->first();

		try {

			DB::beginTransaction();

			if ($foreignMat !== NULL && $foreignMat->material !== NULL) {

				// Update

				/** @var Material $material */
				$material = $foreignMat->material;

				$this->updateMaterial($material);

				// Just compare modified-timestamps and filePath from the last import
				// if ($foreignMat->{ForeignMaterialId::CREATED_AT} != new Carbon($this->localMatInfo->material_created) )
				$this->compareMetaData($bundlesService, $material);
				$this->compareFileAssociations($bundlesService, $material);

				$foreignMat->setCreatedAt($this->localMatInfo->material_created);
				$foreignMat->setUpdatedAt($this->localMatInfo->material_created);

				if ($foreignMat->isDirty()) {
					$foreignMat->saveOrFail();
				}


			} else {

				// Insert
				$material = $this->createMaterial();

				$foreignMat = new ForeignMaterialId(
					[
						'user_id'     => $material->created_by,
						'foreign_id'  => $this->localMatInfo->uuid,
						'material_id' => $material->id,
						'bundle_id'   => $this->bundle->id
					]
				);

				$foreignMat->setCreatedAt($this->localMatInfo->material_modified);
				$foreignMat->setUpdatedAt($this->localMatInfo->material_created);
				$foreignMat->saveOrFail();

				$this->compareMetaData($bundlesService, $material);
				$this->compareFileAssociations($bundlesService, $material);
			}

			DB::commit();
		} catch (\Exception $e) {
			DB::rollBack();

			throw $e;
		}

	}

	protected function getUUID() {
		return $this->localMatInfo->uuid;
	}

	protected function updateMaterial(Material $material) {

		if ($material->title !== $this->localMatInfo->title) {
			$material->title = $this->localMatInfo->title;
		}

		if ($material->description !== $this->localMatInfo->description) {
			$material->description = $this->localMatInfo->description;
		}

		if ($material->rating !== (int) $this->localMatInfo->author_rating) {
			$material->rating = (int) $this->localMatInfo->author_rating;
		}

		if ($material->from_bot !== (bool) $this->localMatInfo->from_bot) {
			$material->from_bot = (bool) $this->localMatInfo->from_bot;
		}

		if (empty($material->author_id) && empty($this->localMatInfo->author_name)) {
			// Both empty
		} else if (empty($this->localMatInfo->author_name)) {
			$material->author_id = NULL;
		} else {
			$author = Person::firstOrCreate(['title' => trim($this->localMatInfo->author_name)]);

			if ($author->id != $material->author_id) {
				$material->author_id = $author->id;
			}
		}

		if ($material->isDirty()) {
			$material->saveOrFail();
		}

		return $material;

	}

	protected function compareMetaData(BundlesService $bundlesService, Material $material) {

		$allMetaData = $bundlesService->getMaterialMetaData($this->bundle, $this->localMatInfo->id);

		/** @var Collection $allExistingKW */
		$allExistingKW = $material->keywords;

		// Check Missing
		foreach ($allMetaData as $metaData) {
			try {
				$testKW = Keyword::make($metaData->value, $metaData->type);

				if ($allExistingKW->contains($testKW)) {
					$found = $allExistingKW->find($testKW);

					if ($testKW->custom_icon != $metaData->custom_icon_path) {
						// TODO Not working yet
					}

					if ($found->pivot->relevance != $metaData->relevance) {
						$this->syncMaterialKeyword($material, $testKW, $metaData->relevance);
					}

					// Remove $testKW from list of existing
					$allExistingKW = $allExistingKW->filter(function ($value) use ($testKW) {
						return !($value->id == $testKW->id);
					});
				} else {
					if ($testKW->isDirty()) {
						$testKW->saveOrFail();
					}

					$this->syncMaterialKeyword($material, $testKW, $metaData->relevance);
				}

			} catch (InvalidResourceTypeException $e) {

			}
		}

		// Remove the rest
		if ($allExistingKW->count() > 0) {
			$material->keywords()->detach($allExistingKW->pluck('id'));
		}

	}

	protected function syncMaterialKeyword(Material $material, Keyword $keyword, $relevance) {
		$material->keywords()->syncWithoutDetaching([$keyword->id => ['relevance' => $relevance]]);

	}

	protected function compareFileAssociations(BundlesService $bundlesService, Material $material) {

		$allAssociatedUUIDs = $bundlesService->getMaterialAssignedFileUUIDs($this->bundle, $this->localMatInfo->id);
		$resourceIDs        = [];

		foreach ($allAssociatedUUIDs as $uuid) {
			/** @var ForeignResourceId $frid */
			$frid          = ForeignResourceId::where('foreign_id', $uuid->uuid)->first();
			$resourceIDs[] = $frid->resource_id;
		}

		$material->resources()->sync($resourceIDs);
	}

	protected function createMaterial() {

		$mat              = new Material();
		$mat->created_by  = 1;
		$mat->modified_by = 1;

		$mat = $this->updateMaterial($mat);

		// $mat->save(); // is done in updateMaterial

		return $mat;
	}
}
