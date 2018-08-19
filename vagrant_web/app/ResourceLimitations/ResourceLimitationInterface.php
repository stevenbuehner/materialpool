<?php

namespace App\ResourceLimitations;

use App\Models\Resource;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\View\View;

interface ResourceLimitationInterface extends Arrayable {

	/**
	 * @return View
	 */
	public function getLimitationView();

	/**
	 * Get the text that is DISPLAYED in a frontend for this limitation
	 *
	 * @return string
	 */
	public function getLimitationText();

	/**
	 * Takes the string, used in the webinterface and extracts all the neccessary limitation data from it
	 *
	 * @return ResourceLimitationInterface
	 * @throws InvalidLimitationRequestException
	 */
	public function insertFromWebValue(string $value);

	/**
	 * Formats the limitation-data back to an string-value, which may be used in the webinterface
	 *
	 * @return string
	 */
	public function toWebValue();

	/**
	 * Returns true if the given Resource is able to use this $limitation
	 *
	 * @param Resource $resource
	 * @return bool
	 */
	public function isResourceApplicable(Resource $resource);

}