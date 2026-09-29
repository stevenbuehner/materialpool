<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OperationalConfigurationCommandsTest extends TestCase {
	public function test_mail_configuration_command_defaults_to_no_and_keeps_existing_configuration(): void {
		config(['mail.configured' => TRUE, 'mail.default' => 'smtp']);

		$exitCode = Artisan::call('mail:configure', ['--no-interaction' => TRUE]);

		$this->assertSame(0, $exitCode);
		$this->assertTrue(config('mail.configured'));
		$this->assertSame('smtp', config('mail.default'));
		$this->assertStringContainsString('wurde nicht geändert', Artisan::output());
	}

	public function test_backup_configuration_command_defaults_to_no_and_keeps_existing_configuration(): void {
		config(['backup.enabled' => TRUE]);

		$exitCode = Artisan::call('backup:configure', ['--no-interaction' => TRUE]);

		$this->assertSame(0, $exitCode);
		$this->assertTrue(config('backup.enabled'));
		$this->assertStringContainsString('wurde nicht geändert', Artisan::output());
	}
}
