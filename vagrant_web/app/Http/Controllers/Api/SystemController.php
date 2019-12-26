<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;

class SystemController extends BaseController {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware('auth');
		$this->middleware('admin');

	}

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function shutdown() {

		// Erst wenn die Antwort an den Browser geschickt wurde
		register_shutdown_function(function () {
			$command = 'sudo /sbin/shutdown -h now';

			// Benötigt den Eintrag in der Suduers Liste: www-data ALL=NOPASSWD: /sbin/shutdown oder wer auch immer den Webbrowser ausführt
			// Bei Laravel-Testumgebung wäre es:  vagrant ALL=NOPASSWD: /sbin/shutdown
			$output = shell_exec($command);
		});


		return response([
			'done' => TRUE,
			// 'use'  => shell_exec('whoami')
		]);
	}
}
