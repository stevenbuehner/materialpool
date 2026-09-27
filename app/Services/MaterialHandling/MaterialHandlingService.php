<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\MaterialHandling;


use App\Events\MaterialWasCreated;
use App\Events\MaterialWasDeleted;
use App\Events\ResourceWasDetached;
use App\Jobs\CheckLonelyBibleverse;
use App\Jobs\CheckLonelyKeyword;
use App\Jobs\CheckLonelyResource;
use App\Models\File;
use App\Models\Keyword;
use App\Models\Material;
use App\Services\ResourceHandling\Exceptions\LocalFileDoesNotExistException;
use App\Services\ResourceHandling\Exceptions\RemoteFileDoesNotExistException;
use App\Services\ResourceHandling\FileHandlingService;
use App\Services\TagExtraction\ResourceHandles\TextContentInterface;
use Illuminate\Support\Arr;

class MaterialHandlingService {

	/**
	 * @param Material $material
	 * @throws \Exception
	 */
	public function deleteMaterialAndDetachAssociations(Material $material) {

		// Important for MaterialWasDeleted event (!)
		$material->load(['resources', 'bibleverses', 'keywords', 'foreignIds']);

		$this->detachAllResources($material);
		$this->detachAllBibleverses($material);
		$this->detachAllKeywords($material);
		$this->detachAllForeignIds($material);
		$this->detachAuthor($material);

		$material->delete();
		event(new MaterialWasDeleted($material));

	}

	public function detachAllResources(Material $material) {

		$material->resources;
		$material->resources()->detach();

		foreach ($material->resources as $resource) {
			CheckLonelyResource::dispatch($resource);
			event(new ResourceWasDetached($material, $resource));
		}

	}

	public function detachAllBibleverses(Material $material) {

		$material->bibleverses;
		$material->bibleverses()->detach();

		foreach ($material->bibleverses as $bv) {
			CheckLonelyBibleverse::dispatch($bv);
		}

	}

	public function detachAllKeywords(Material $material) {

		$material->keywords;
		$material->keywords()->detach();

		foreach ($material->keywords as $kw) {
			CheckLonelyKeyword::dispatch($kw);
		}

	}

	public function detachAllForeignIds(Material $material) {

		$material->foreignIds;
		$material->foreignIds()->delete();

	}

	public function detachAuthor(Material $material) {

		$author = $material->author;

		if ($author instanceof Keyword) {
			$material->author()->dissociate();    // Keep this information for MaterialWasDeleted-Event
			CheckLonelyKeyword::dispatch($author);
		}

	}

	/**
	 * @param Material $material
	 * @return Material
	 */
	public function copyMaterial(Material $material) {

		/** @var Material $clone */
		$clone = $material->replicate();
		$clone->created_at = $material->created_at;
		$clone->save();
		// $clone->setRelations([]);

		$relationsToSync = [
			'keywords',
			'bibleverses',
			'resources'
		];

		$material->load($relationsToSync);

		// Once the model has been saved with a new ID, we can get its children
		foreach ($relationsToSync as $relationName) {
			$attachKeys = [];

			foreach ($material->getRelation($relationName) as $item) {
				// Now we get the extra attributes from the pivot tables, but
				// we intentionally leave out the foreignKey, as we already
				// have it in the newModel
				$extra_attributes            = Arr::except($item->pivot->getAttributes(),
					[$item->pivot->getForeignKey(), $item->pivot->getRelatedKey()]);
				$attachKeys[$item->getKey()] = $extra_attributes;
			}

			$clone->{$relationName}()->sync($attachKeys);

		}

		event(new MaterialWasCreated($clone));

		return $clone->fresh(\App\Http\Controllers\MaterialController::withAttributes());

	}

	public function createZipDownloadOfMaterialContents(Material $material) {
		$subPathInPublic = 'downloads';
		$public_dir      = public_path($subPathInPublic);
		$baseDir         = $this->createFilenameFromMaterial($material); // String ohne Datei-Extension
		$zipFileName     = 'Material_' . $material->id . '_' . uniqid() . '.zip';
		$zip             = new \ZipArchive();

		/** @var FileHandlingService $fileHandlingService */
		$fileHandlingService = resolve(FileHandlingService::class);

		if ($zip->open($public_dir . DIRECTORY_SEPARATOR . $zipFileName, \ZipArchive::CREATE) === TRUE) {

			$zip->filename =  $this->createFilenameFromMaterial($material); // Scheint nicht zu funktionieren
			$zip->setArchiveComment('All the resources from material ' . $material->id);

			foreach ($material->resources as $resource) {

				if ($resource instanceof File) {

					try {
						$absPath = $fileHandlingService->getLocalFilePath($resource);
						$zip->addFile($absPath, $baseDir . DIRECTORY_SEPARATOR . $resource->original_filename);
					} catch (LocalFileDoesNotExistException $e) {
						\Log::error($e->getMessage(), $resource->toArray());
					} catch (RemoteFileDoesNotExistException $e) {
						\Log::error($e->getMessage(), $resource->toArray());
					}

				} else if ($resource instanceof TextContentInterface) {

					$path = tempnam(sys_get_temp_dir(), 'res_' . $resource->id . '_');
					file_put_contents($path, $resource->getContent());
					$zip->addFile($path, $baseDir . DIRECTORY_SEPARATOR . 'Textresource_' . $resource->id . '.txt');

				}
			}

			$zip->close();

		}

		return DIRECTORY_SEPARATOR . $subPathInPublic . DIRECTORY_SEPARATOR . $zipFileName;
	}

	/**
	 * Gibt einen String aus dem Titel zurück, ohne eine Datei-Extension
	 * @param Material $material
	 * @param int $minLength
	 * @param int $maxLength
	 * @return string
	 */
	protected function createFilenameFromMaterial(Material $material, int $minLength = 5, int $maxLength = 80) {

		if ($minLength > $maxLength) {
			// Swap
			list($minLength, $maxLength) = array($maxLength, $minLength);
		}

		$filename = $material->title;

		// Ersetze Umlaute
		$umlaute  = ["~ä~", "~ö~", "~ü~", "~Ä~", "~Ö~", "~Ü~", "~ß~"];
		$replace  = ["~ae~", "~oe~", "~ue~", "~Ae~", "~Oe~", "~Ue~", "~ss~"];
		$filename = preg_replace($umlaute, $replace, $filename);

		// Ersetze alle Zeichen, die es nicht geben darf
		$filename = preg_replace('~[^a-z-A-Z0-9-_\(\)\,]~', '', $filename);

		// Limitiere Zeichenlänge
		$filename = trim(substr($filename, 0, $maxLength));

		if (strlen($filename) < $minLength) {
			$filename = 'Material Collection_' . $filename;
		}

		return $filename;

	}


}
