<?php

namespace Tests\Unit;

use App\Services\QueueJobDetailsPresenter;
use Tests\TestCase;

class QueueJobDetailsPresenterTest extends TestCase {
	public function test_invalid_json_and_encrypted_commands_are_not_exposed_as_raw_data(): void {
		$presenter = app(QueueJobDetailsPresenter::class);
		$this->assertNull($presenter->payload('{invalid-json'));

		$payload = $presenter->payload(json_encode(['displayName' => 'Example', 'data' => ['command' => 'encrypted-command']]));
		$this->assertSame('Example', $payload['displayName']);
		$this->assertSame(__('pool.queue-command-unavailable'), $payload['data']['jobData']);
		$this->assertArrayNotHasKey('command', $payload['data']);
	}
}
