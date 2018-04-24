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

class DeleteMaterialIfNeeded implements ShouldQueue {
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


	/** @var  Bundle $bundle */
	protected $bundle;
	protected $foreignMaterialId;

	protected $localResourceId;

	public function __construct(Bundle $bundle, ForeignMaterialId $foreignMaterialId) {
		$this->bundle            = $bundle;
		$this->foreignMaterialId = $foreignMaterialId;

	}

	/**
	 * Execute the job.
	 *
	 */
	public function handle(BundlesService $bundlesService) {

		$uuid = $this->foreignMaterialId->foreign_id;

		if (!$bundlesService->hasMaterial($this->bundle, $uuid)) {

			$materials = $this->foreignMaterialId->material;

			/** @var Material $mat */
			foreach ($materials as $mat) {
				if ($mat->from_bot == TRUE) {
					// Nothing was changed by the user

					$mat->resources()->detach();

					foreach ($mat->keywords as $kw) {
						CheckLonelyKeyword::dispatch($kw)->onQueue($this->queue)->onConnection($this->connection);
					}
					$mat->keywords()->detach();

					foreach ($mat->bibleverses() as $bv) {
						CheckLonelyBibleverse::dispatch($bv)->onQueue($this->queue)->onConnection($this->connection);
					}
					$mat->bibleverses()->detach();

				}
			}

		} else {
			// Material still exists => Nothing to do
		}

	}
}
