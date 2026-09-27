<?php

namespace App\Services\System;

class SystemShutdownService {

	/**
	 * @return bool
	 */
	public function shutdownSystemNow() {

		$command = 'sudo /sbin/shutdown -h now';
		// Benötigt den Eintrag in der Suduers Liste: www-data ALL=NOPASSWD: /sbin/shutdown oder wer auch immer den Webbrowser ausführt
		// Bei Laravel-Testumgebung wäre es:  vagrant ALL=NOPASSWD: /sbin/shutdown
		$output = shell_exec($command);

		return $output;

	}

}

?>