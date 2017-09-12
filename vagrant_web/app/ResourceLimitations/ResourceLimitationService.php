<?php
/**
 * This file was created by  steven
 * Created: 12.09.17 11:30
 * All Rights reserved. No usage without written permission allowed.
 */

namespace App\ResourceLimitations;


class ResourceLimitationService {

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
			$limitation = new  $className($data['value']);
		} catch (\Exception $e) {
			throw new InvalidLimitationRequestException('Could not create Limitation', 0, $e);
		}

		$limitation->insertFromWebValue($data['value']);

		return $limitation;
	}


}