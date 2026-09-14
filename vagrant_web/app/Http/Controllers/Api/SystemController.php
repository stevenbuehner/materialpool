<?php

namespace App\Http\Controllers\Api;

use App\Services\System\SystemShutdownService;
use Illuminate\Routing\Controller as BaseController;

class SystemController extends BaseController {

	protected $systemShutdownService;

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(SystemShutdownService $systemShutdownService) {
		$this->systemShutdownService = $systemShutdownService;
	}

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function shutdown() {

		// Erst wenn die Antwort an den Browser geschickt wurde
		register_shutdown_function(function () {

			$output = $this->systemShutdownService->shutdownSystemNow();

			// $command = 'sudo /sbin/shutdown -h now';
			// Benötigt den Eintrag in der Suduers Liste: www-data ALL=NOPASSWD: /sbin/shutdown oder wer auch immer den Webbrowser ausführt
			// Bei Laravel-Testumgebung wäre es:  vagrant ALL=NOPASSWD: /sbin/shutdown
			// $output = shell_exec($command);
		});


		return response([
			'done' => TRUE,
			// 'use'  => shell_exec('whoami')
		]);
	}
}
