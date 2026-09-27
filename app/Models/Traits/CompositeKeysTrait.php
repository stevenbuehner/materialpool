<?php

namespace App\Models\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;

/**
 */
trait CompositeKeysTrait {
	/**
	 * Execute a query for a single record by ID.
	 *
	 * @param array $ids Array of keys, like [column => value].
	 * @param array $columns
	 * @return mixed|static
	 */
	public static function find($ids, $columns = ['*']) {
		$me    = new self;
		$query = $me->newQuery();
		foreach ($me->getKeyName() as $key) {
			$query->where($key, '=', $ids[$key]);
		}

		return $query->first($columns);
	}

	/**
	 * Get the value indicating whether the IDs are incrementing.
	 *
	 * @return bool
	 */
	public function getIncrementing() {
		return FALSE;
	}

	// UPDATE: From jessedp. See his edit, below.

	/**
	 * Set the keys for a save update query.
	 *
	 * @param Builder $query
	 * @return Builder
	 */
	protected function setKeysForSaveQuery(Builder $query) {
		foreach ($this->getKeyName() as $key) {
			// UPDATE: Added isset() per devflow's comment.
			if (isset($this->$key)) {
				$query->where($key, '=', $this->$key);
			} else {
				throw new Exception(__METHOD__ . 'Missing part of the primary key: ' . $key);
			}
		}

		return $query;
	}
}