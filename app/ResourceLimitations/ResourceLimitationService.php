<?php
/**
 * This file was created by  steven
 * Created: 12.09.17 11:30
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\ResourceLimitations;


use App\Models\Material;
use App\Models\Resource;

class ResourceLimitationService {

	/**
	 * @param          $type
	 * @param          $value
	 * @param Resource $resource
	 * @param Material $material
	 * @throws InvalidLimitationRequestException
	 * @throws LimitationNotApplicableForResource
	 */
	public function createAndAssignLimitation($type, $value, Resource $resource, Material $material) {

		$limitation = $this->createLimitation(['type' => $type, 'value' => $value]);

		if (FALSE === $limitation->isResourceApplicable($resource)) {
			throw new LimitationNotApplicableForResource();
		}

		$material->resources()->syncWithoutDetaching([$resource->id => ['limitation' => $limitation]]);

	}

	/**
	 * @param array $data
	 * @return ResourceLimitationInterface
	 * @throws InvalidLimitationRequestException
	 */
	public function createLimitation($data) {
		if (!isset($data['type'])) {
			throw new InvalidLimitationRequestException('Missing Limitation-Type');
		} else if (!isset($data['value'])) {
			throw new InvalidLimitationRequestException('Missing Limitation-Value');
		}

		$className = 'App\ResourceLimitations\\' . ucfirst(strtolower($data['type'])) . 'Limitation';

		try {
			/** @var ResourceLimitationInterface $limitation */
			$limitation = new  $className($data['value']);
		} catch (\Error $e) {
			throw new InvalidLimitationRequestException('Could not create Limitation', 0, $e);
		}

		$limitation->insertFromWebValue($data['value']);

		return $limitation;
	}

	public function getLimitationData(ResourceLimitationInterface $limitation) {
		return [
			'value' => $limitation->toWebValue(),
			'type'  => $this->getLimitationType($limitation)
		];
	}

	/**
	 *
	 * @param ResourceLimitationInterface $limitation
	 * @return string
	 * @throws \Exception
	 */
	public function getLimitationType(ResourceLimitationInterface $limitation) {
		$basename = class_basename(get_class($limitation));
		$type     = preg_replace('~^([a-zA-Z]+)Limitation$~', '$1', $basename);

		if ($type == $basename) {
			throw new \Exception('Invalid LimitationName');
		}

		return $type;

	}


}