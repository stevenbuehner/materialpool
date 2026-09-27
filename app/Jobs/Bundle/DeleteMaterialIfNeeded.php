<?php

namespace App\Jobs\Bundle;

use App\Models\Bundle;
use App\Models\ForeignMaterialId;
use App\Models\Material;
use App\Services\Bundles\BundlesService;
use App\Services\MaterialHandling\MaterialHandlingService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Log;

class DeleteMaterialIfNeeded implements ShouldQueue, VersionInterface {
	use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $timeout = 120;


	/** @var  Bundle $bundle */
	protected $bundle;
	protected $foreignMaterialId;
	protected $version;
	protected $uninstall;

	public function __construct(Bundle $bundle, ForeignMaterialId $foreignMaterialId, $version, $uninstall = FALSE) {
		$this->bundle            = $bundle;
		$this->foreignMaterialId = $foreignMaterialId;
		$this->version           = $version;
		$this->uninstall         = $uninstall;
	}

	/**
	 * Execute the job.
	 * Prüft, ob ein existierendes Material noch immer im externen Bundle vorhanden ist.
	 * Falls das externe Bundle dieses Material nicht mehr besitzt, wird das lokale Material ebenfalls gelöscht.
	 * Vorausgesetzt, es wurde nicht vom Nutzer bearbeitet.
	 *
	 */
	public function handle(BundlesService $bundlesService, MaterialHandlingService $materialHandlingService) {
		if ($this->batch()?->cancelled()) {
			return;
		}

		$uuid = $this->foreignMaterialId->foreign_id;

		if ($this->uninstall || !$bundlesService->hasMaterial($this->bundle, $uuid)) {

			$mat = $this->foreignMaterialId->material;

			/** @var Material $mat */
			if ($mat && $mat->from_bot === TRUE) {

				// Nicht löschen, wenn noch andere ForeinId Verknüpfungen bestehen
				/** @var ForeignMaterialId $foreignId */
				if ($mat->foreignIds->count() === 1) {
					// Lösche Material
					$materialHandlingService->deleteMaterialAndDetachAssociations($mat);
				} else {
					// Mehrere ForeignIds sind mit diesem Material verknüpft! => Material nicht löschen!
					Log::info('Material wird nicht gelöscht, weil nach andere ForeignIds existieren!', $mat->foreignIds->toArray());
				}
			} else {
				// Material was Cch
			}

			$this->foreignMaterialId->delete();

		} else {
			// Material still exists in the external bundle  => Nothing to do
		}


	}

	public function middleware(): array {
		return [(new WithoutOverlapping('bundle:' . $this->bundle->id . ':delete-material:' . $this->foreignMaterialId->id))
			        ->shared()
			        ->releaseAfter(5)
			        ->expireAfter(180)];
	}

	public function getVersion() {
		return $this->version;
	}
}
