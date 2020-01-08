<?php
/**
 * This file was created by  steven
 * Created: 23.08.17 23:13
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\Services\ResourceHandling;


use App\Events\ResourceWasDetached;
use App\Models\File;
use App\Models\Resource;
use App\Models\Traits\PageCountTrait;
use App\Models\Traits\TimeCountTrait;
use App\Models\User;
use App\ResourceLimitations\PageLimitation;
use App\ResourceLimitations\TimeLimitation;
use App\Services\ResourceHandling\Exceptions\InvalidResourceTypeException;
use App\Services\TagExtraction\ResourceHandles\TextContentInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ResourceHandlingService {

	public function detachAllMaterials(Resource $resource) {

		foreach ($resource->materials as $material) {
			$resource->materials()->detach($material->id);
			event(new ResourceWasDetached($material, $resource));
		}

	}

	public function detachAllForeignIds(Resource $resource) {

		$resource->foreignIds()->delete();

	}

	public function replaceResource(Resource $oldResource, Resource $newResource, ?User $user = NULL) {

		if ($user === NULL) {
			$user = Auth::user();
		}

		// Checks
		if (FALSE === $this->isReplaceableChecks($oldResource, $newResource, $user)) {
			return FALSE;
		}

		// Action - wenn alles klappt
		// Alte Resource archivieren
		list($storage, $path) = $this->archiveResource($oldResource);

		// Alle Verknüpfungen auf die neue Resource umlegen
		/** @var ResourceDuplicationHandlingService $handler */
		$handler = resolve(ResourceDuplicationHandlingService::class);
		$handler->migrateSlaveIntoMasterResource($oldResource, $newResource);

		// Todo: Change-Events für die Resource und alle Verknüpfungen abfeuern

		// Resource neu laden und zurückgeben
		return $newResource->refresh();

	}

	public function isReplaceableChecks(Resource $oldResource, Resource $newResource, User $user) {

		// Checks
		if ($newResource->id === $oldResource->id) {
			Log::warning('The resources are equal. --> do not replace it');
			return FALSE;
		}

		// 1. Authorisation (nur Creator erlaubt)
		if ($user->id !== $oldResource->created_by || $user->id !== $newResource->created_by) {
			Log::warning('One of the resources is not owned by the required user. --> do not replace it');
			return FALSE;
		}

		// 2. Typ (nur identischer Typ erlaubt)
		if ($oldResource->type !== $newResource->type) {
			Log::warning('Resources have different types --> do not replace it.');
			return FALSE;
		}

		// 3. Keine Seitenanzahl/Videolänge/... Änderungen, wenn Limitierungsverknüpfugen existieren
		$checkLimitationChanges = NULL;
		foreach ($oldResource->materials as $mat) {
			$checkLimitationChanges = $mat->pivot->limitation;
			if ($checkLimitationChanges !== NULL) {
				break;
			}
		}

		if ($checkLimitationChanges instanceof PageLimitation) {

			$pagesOld = ($oldResource instanceof PageCountTrait) ? $oldResource->getPageCountAttribute() : NULL;
			$pagesNew = ($newResource instanceof PageCountTrait) ? $newResource->getPageCountAttribute() : NULL;

			if ($pagesOld !== $pagesNew) {
				Log::warning('Resources have different page-sizes --> do not replace it.');
				return FALSE;
			}
		} else if ($checkLimitationChanges instanceof TimeLimitation) {

			$timeOld = ($oldResource instanceof TimeCountTrait) ? $oldResource->getTimeCountAttribute() : NULL;
			$timeNew = ($newResource instanceof TimeCountTrait) ? $newResource->getTimeCountAttribute() : NULL;

			if ($timeOld !== $timeNew) {
				Log::warning('Resources have different time-length --> do not replace it.');
				return FALSE;
			}
		}

		// Todo: 4. wenn "ist_public" => stimmt dann public_path noch?

		// 5. Gehört Resource zu einem Bundle => Nicht erlauben oder nur mit NICHT-Bundle Materialien verknüpfen
		if ($oldResource->foreignIds->count() > 0) {
			Log::warning('The resource you want to replace is assigned to bundle --> do not replace it.');
			return FALSE;
		}
		/*
		if ($newResource->foreignIds->count() > 0) {
			Log::warning('The resource you want to replace this one with is assigned to bundle --> do not use it as replacement.');
			return FALSE;
		}
		*/

		return TRUE;

	}

	/**
	 * @param Resource $resource
	 * @return mixed
	 * @throws InvalidResourceTypeException
	 */
	public function archiveResource(Resource $resource) {

		$handler = NULL;
		if ($resource instanceof File) {
			/** @var FileHandlingService $fileHandling */
			$handler = resolve(FileHandlingService::class);
		} else if ($resource instanceof TextContentInterface) {
			$handler = resolve(TextHandlingService::class);
		}

		if ($handler) {
			return $handler->archiveResource($resource);
		} else {
			throw new InvalidResourceTypeException('No Archive-Service found for this resource type');
		}

	}


}