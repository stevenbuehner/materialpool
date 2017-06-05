<?php

namespace Modules\IdeaSpektrumGrabber\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\IdeaSpektrumGrabber\Listeners\GrabberRegisterListener;
use Modules\MaterialGrabber\Events\GrabberRegister;

class IdeaSpektrumGrabberServiceProvider extends ServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = FALSE;

	/**
	 * Boot the application events.
	 *
	 * @return void
	 */
	public function boot() {
		Event::listen(GrabberRegister::class, GrabberRegisterListener::class);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		//
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return [];
	}
}
