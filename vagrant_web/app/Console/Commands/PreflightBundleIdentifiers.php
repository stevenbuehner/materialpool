<?php

namespace App\Console\Commands;

use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\ForeignResourceId;
use Illuminate\Console\Command;

class PreflightBundleIdentifiers extends Command {
	protected $signature = 'bundles:preflight-identifiers';

	protected $description = 'Prüft Bundle-UUIDs und Bundle-Foreign-IDs read-only auf Constraint-Konflikte.';

	public function handle(): int {
		$emptyUuids = Bundle::query()->whereNull('uuid')->orWhere('uuid', '')->count();
		$duplicateUuids = Bundle::query()
			->whereNotNull('uuid')
			->where('uuid', '!=', '')
			->select('uuid')
			->groupBy('uuid')
			->havingRaw('COUNT(*) > 1')
			->get()
			->count();
		$materialCollisions = $this->bundleForeignIdCollisionCount(ForeignMaterialId::query());
		$resourceCollisions = $this->bundleForeignIdCollisionCount(ForeignResourceId::query());

		$this->table(['Prüfung', 'Anzahl'], [
			['Leere Bundle-UUIDs', $emptyUuids],
			['Doppelte Bundle-UUIDs', $duplicateUuids],
			['Material-Foreign-IDs in mehreren Bundles', $materialCollisions],
			['Resource-Foreign-IDs in mehreren Bundles', $resourceCollisions],
		]);

		if ($emptyUuids + $duplicateUuids + $materialCollisions + $resourceCollisions > 0) {
			$this->error('Preflight nicht bestanden. Keine Constraint-Migration oder Datenkorrektur ausführen.');

			return self::FAILURE;
		}

		$this->info('Preflight bestanden. Die Prüfung war read-only.');

		return self::SUCCESS;
	}

	private function bundleForeignIdCollisionCount($query): int {
		return $query
			->whereNotNull('bundle_id')
			->select('foreign_id')
			->groupBy('foreign_id')
			->havingRaw('COUNT(DISTINCT bundle_id) > 1')
			->get()
			->count();
	}
}
