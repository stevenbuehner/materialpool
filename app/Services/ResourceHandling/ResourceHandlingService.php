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
use App\Services\ResourceHandling\Exceptions\ResourceNotReplaceable;
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

	/**
	 * @param Resource $oldResource
	 * @param Resource $newResource
	 * @param User|null $user
	 * @return Resource
	 * @throws ResourceNotReplaceable
	 */
	public function replaceResource(Resource $oldResource, Resource $newResource, ?User $user = NULL) {

		if ($user === NULL) {
			$user = Auth::user();
		}

		// Checks
		$this->isReplaceableChecks($oldResource, $newResource, $user);

		// Action - wenn alles klappt
		// Alte Resource archivieren
		try {
			list($storage, $path) = $this->archiveResource($oldResource);
		} catch (InvalidResourceTypeException $e) {
			throw new ResourceNotReplaceable($e);
		}

		// Alle Verknüpfungen auf die neue Resource umlegen
		/** @var ResourceDuplicationHandlingService $handler */
		$handler = resolve(ResourceDuplicationHandlingService::class);
		try {
			$handler->migrateSlaveIntoMasterResource($oldResource, $newResource);
		} catch (\Exception $e) {
			throw new ResourceNotReplaceable($e);
		}

		// Resource neu laden und zurückgeben
		return $newResource->refresh();

	}

	public function isReplaceableChecks(Resource $oldResource, Resource $newResource, User $user) {

		// Checks
		if ($newResource->id === $oldResource->id) {
			throw new ResourceNotReplaceable('The two given resources are equal');
		}

		// 1. Authorisation (nur Creator erlaubt)
		if ($user->id !== $oldResource->created_by || $user->id !== $newResource->created_by) {
			throw new ResourceNotReplaceable('One of the resources is not owned by the required user');
		}

		// 2. Typ (nur identischer Typ erlaubt)
		if ($oldResource->type !== $newResource->type) {
			throw new ResourceNotReplaceable('Resources have different types');
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

			$pagesOld = in_array(PageCountTrait::class, class_uses_recursive($oldResource)) ? $oldResource->getPageCountAttribute() : NULL;
			$pagesNew = in_array(PageCountTrait::class, class_uses_recursive($newResource)) ? $newResource->getPageCountAttribute() : NULL;

			if ($pagesOld !== $pagesNew) {
				throw new ResourceNotReplaceable('Resources have different page-sizes');
			}
		} else if ($checkLimitationChanges instanceof TimeLimitation) {

			$timeOld = in_array(TimeCountTrait::class, class_uses_recursive($oldResource)) ? $oldResource->getTimeCountAttribute() : NULL;
			$timeNew = in_array(TimeCountTrait::class, class_uses_recursive($newResource)) ? $newResource->getTimeCountAttribute() : NULL;

			if ($timeOld !== $timeNew) {
				throw new ResourceNotReplaceable('Resources have different time-length');
			}
		}

		// Todo: 4. wenn "ist_public" => stimmt dann public_path noch?

		// 5. Gehört Resource zu einem Bundle => Nicht erlauben oder nur mit NICHT-Bundle Materialien verknüpfen
		if ($oldResource->foreignIds->count() > 0) {
			throw new ResourceNotReplaceable('The resource you want to replace is assigned to bundle');
		}
		/*
		if ($newResource->foreignIds->count() > 0) {
			throw new ResourceNotReplaceable('The resource you want to replace this one with is assigned to bundle');
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