<?php

namespace App\Jobs\Bundle;

use App\Jobs\CheckLonelyBibleverse;
use App\Jobs\CheckLonelyKeyword;
use App\Models\Bibleverse;
use App\Models\Bundle;
use App\Models\Exceptions\InvalidKeywordTypeException;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use App\Models\Keyword;
use App\Models\Material;
use App\Services\Bundles\BundlesService;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsertOrUpdateMaterial implements ShouldQueue, VersionInterface {
	use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 120;


	/** @var  Bundle $bundle */
	protected $bundle;

	protected $localMatInfo;
	protected $version;

	/**
	 * InsertOrUpdateResource constructor.
	 *
	 * @param Bundle $bundle
	 * @param        $localFileInfo
	 */
	public function __construct(Bundle $bundle, $localMaterialInfo, $version) {
		$this->bundle       = $bundle;
		$this->localMatInfo = $localMaterialInfo;
		$this->version      = $version;

	}

	/**
	 * Execute the job.
	 *
	 */
	public function handle(BundlesService $bundlesService) {
		if ($this->batch()?->cancelled()) {
			return;
		}

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

	public function middleware(): array {
		return [(new WithoutOverlapping('bundle:' . $this->bundle->id . ':upsert-material:' . $this->getUUID()))
			->shared()
			->releaseAfter(5)
			->expireAfter(180)];
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

		if ($material->rating !== (int)$this->localMatInfo->author_rating) {
			$material->rating = (int)$this->localMatInfo->author_rating;
		}

		if ($material->from_bot !== (bool)$this->localMatInfo->from_bot) {
			$material->from_bot = (bool)$this->localMatInfo->from_bot;
		}

		if ($material->icon_of_bundle !== $this->bundle->icon) {
			$material->icon_of_bundle = $this->bundle->id;
		}

		if (empty($material->author_id) && empty($this->localMatInfo->author_name)) {
			// Both empty
		} else if (empty($this->localMatInfo->author_name)) {
			$material->author_id = NULL;
		} else {
			$author = Keyword::firstOrCreatePerson(trim($this->localMatInfo->author_name));

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
		/** @var Collection $allExistingBV */
		$allExistingKW = $material->keywords;
		$allExistingBV = $material->bibleverses;

		// Check Missing
		foreach ($allMetaData as $metaData) {

			switch ($metaData->type) {
				case 'key':
				case 'person':
				case 'place':
				case 'lang':
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

					} catch (InvalidKeywordTypeException $e) {

					}
					break;

				case 'bibleverse':

					if (preg_match('~^(\d+):(\d+):(\d+)\-(\d+):(\d+):(\d+)$~', $metaData->value, $match) === 1) {
						$from_book    = $match[1];
						$from_chapter = $match[2];
						$from_verse   = $match[3];
						$to_book      = $match[4];
						$to_chapter   = $match[5];
						$to_verse     = $match[6];


						$bv = new Bibleverse([
							'from_book_id' => $from_book,
							'from_chapter' => $from_chapter,
							'from_verse'   => $from_verse,
							'to_book_id'   => $to_book,
							'to_chapter'   => $to_chapter,
							'to_verse'     => $to_verse,
						]);
						$bv = Bibleverse::findOrCreateFromBibleverseInterface($bv);


						if ($allExistingBV->contains($bv)) {
							$found = $allExistingBV->find($bv);

							if ($found->pivot->relevance != $metaData->relevance) {
								$this->syncMaterialBibleverse($material, $bv, $metaData->relevance);
							}

							// Remove $testBV from list of existing
							$allExistingBV = $allExistingBV->filter(function ($value) use ($bv) {
								return !($value->id == $bv->id);
							});
						} else {
							if ($bv->isDirty()) {
								$bv->saveOrFail();
							}

							$this->syncMaterialBibleverse($material, $bv, $metaData->relevance);
						}


					} else {
						Log::error('Meta-Data Bibleverse not recognized: ', [$metaData->value]);
					}

					break;

				default:
					Log::error('Meta-Data-Type not recognized: ', [$metaData]);
			}


		}

		// Remove the rest
		if ($allExistingKW->count() > 0) {
			$material->keywords()->detach($allExistingKW->pluck('id'));
			$allExistingKW->each(function ($kw) {
				CheckLonelyKeyword::dispatch($kw)->onConnection($this->connection);;
			});
		}

		// Remove the rest
		if ($allExistingBV->count() > 0) {
			$material->bibleverses()->detach($allExistingBV->pluck('id'));
			$allExistingBV->each(function ($bv) {
				CheckLonelyBibleverse::dispatch($bv)->onConnection($this->connection);;
			});
		}

	}

	protected function syncMaterialKeyword(Material $material, Keyword $keyword, $relevance) {
		$material->keywords()->syncWithoutDetaching([$keyword->id => ['relevance' => $relevance]]);

	}

	protected function syncMaterialBibleverse(Material $material, Bibleverse $bv, $relevance) {
		$material->bibleverses()->syncWithoutDetaching([$bv->id => ['relevance' => $relevance]]);
	}

	protected function compareFileAssociations(BundlesService $bundlesService, Material $material) {

		$allAssociatedUUIDs = $bundlesService->getMaterialAssignedFileUUIDs($this->bundle, $this->localMatInfo->id);
		$resourceIDs        = [];

		foreach ($allAssociatedUUIDs as $uuid) {
			/** @var ForeignResourceId $frid */
			$frid = ForeignResourceId::where('foreign_id', $uuid->uuid)->first();

			if ($frid) {
				$resourceIDs[] = $frid->resource_id;
			} else {
				Log::error('It seems like there is a resource missing, which should have been synced',
					[
						'material'    => $material,
						'missingUUID' => $uuid
					]);
			}
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

	public function getVersion() {
		return $this->version;
	}
}
