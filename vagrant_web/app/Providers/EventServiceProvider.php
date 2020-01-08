<?php

namespace App\Providers;

use App\Events\MaterialWasChanged;
use App\Events\MaterialWasCreated;
use App\Events\MaterialWasDeleted;
use App\Events\ResourceWasAttached;
use App\Events\ResourceWasChanged;
use App\Events\ResourceWasCreated;
use App\Events\ResourceWasDeleted;
use App\Events\ResourceWasDetached;
use App\Jobs\ClearMaterialPreviewCache;
use App\Listeners\CalculateDocPageSize;
use App\Listeners\CalculatePdfPageSize;
use App\Listeners\ClearResourcePreviewCache;
use App\Listeners\Queued\CheckDuplicateResources;
use App\Listeners\UpdateResourceHashes;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider {
	/**
	 * The event listener mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = [
		MaterialWasCreated::class => [
			// CheckDuplicateMaterials::class,
		],

		MaterialWasChanged::class => [
			// Clear and/or Update Caches

			// CheckDuplicateMaterials::class,
		],
		MaterialWasDeleted::class => [
			// Check all detached keywords and bibleverses for lonelyness

			// Clear all material Caches
			ClearMaterialPreviewCache::class,
		],

		ResourceWasCreated::class => [
			// Create Hash
			UpdateResourceHashes::class,

			// Do Media-Specific stuff: Count PDF-Pages / Video-Seconds / Audio-Seconds / ...
			CalculatePdfPageSize::class,
			CalculateDocPageSize::class,

			CheckDuplicateResources::class,
			// CheckDuplicateMaterials::class,

			//
		],
		ResourceWasChanged::class => [
			// Update Hash and if changed =>  Do Media-Specific stuff: Count PDF-Pages / Video-Seconds / Audio-Seconds / ...
			UpdateResourceHashes::class,

			// Do Media-Specific stuff: Count PDF-Pages / Video-Seconds / Audio-Seconds / ...
			CalculatePdfPageSize::class,
			CalculateDocPageSize::class,

			CheckDuplicateResources::class,
			// CheckDuplicateMaterials::class,
			//
		],
		ResourceWasDeleted::class => [
			// Clear all Caches
			ClearResourcePreviewCache::class,
		],

		ResourceWasAttached::class => [
			// Update Material-Preview Hashes etc
		],
		ResourceWasDetached::class => [
			// Update Material-Preview Hashes etc
		]

	];

	/**
	 * Register any events for your application.
	 *
	 * @return void
	 */
	public function boot() {
		parent::boot();

		Event::listen('App\Events\*', function ($eventName, $data) {
			$data = $data;
		});

		//
	}
}
