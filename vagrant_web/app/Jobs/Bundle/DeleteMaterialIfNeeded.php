<?php

namespace App\Jobs\Bundle;

use App\Jobs\CheckLonelyBibleverse;
use App\Jobs\CheckLonelyKeyword;
use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\Material;
use App\Services\Bundles\BundlesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteMaterialIfNeeded implements ShouldQueue, VersionInterface {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


	/** @var  Bundle $bundle */
	protected $bundle;
	protected $foreignMaterialId;

	protected $localResourceId;
	protected $version;

	public function __construct(Bundle $bundle, ForeignMaterialId $foreignMaterialId, $version) {
		$this->bundle            = $bundle;
		$this->foreignMaterialId = $foreignMaterialId;
		$this->version           = $version;
	}

	/**
	 * Execute the job.
	 *
	 */
	public function handle(BundlesService $bundlesService) {

		$uuid = $this->foreignMaterialId->foreign_id;

		if (!$bundlesService->hasMaterial($this->bundle, $uuid)) {

			$mat = $this->foreignMaterialId->material;

			/** @var Material $mat */
			if ($mat && $mat->from_bot == TRUE) {
				// Nothing was changed by the user

				$mat->resources()->detach();

				foreach ($mat->keywords as $kw) {
					// CheckLonelyKeyword::dispatch($kw)->onQueue($this->queue)->onConnection($this->connection);
					// Use default Queue (to speed up import-process)
					CheckLonelyKeyword::dispatch($kw)->onConnection($this->connection);
				}

				if ($mat->keywords->count() > 0) {
					$mat->keywords()->detach();
				}


				foreach ($mat->bibleverses as $bv) {
					// CheckLonelyBibleverse::dispatch($bv)->onQueue($this->queue)->onConnection($this->connection);
					// Use default Queue (to speed up import-process)
					CheckLonelyBibleverse::dispatch($bv)->onConnection($this->connection);
				}

				if ($mat->bibleverses->count() > 0) {
					$mat->bibleverses()->detach();
				}
			}

			$this->foreignMaterialId->delete();

		} else {
			// Material still exists => Nothing to do
		}


	}

	public function getVersion() {
		return $this->version;
	}
}
