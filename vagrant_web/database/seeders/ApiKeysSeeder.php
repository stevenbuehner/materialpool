<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class ApiKeysSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run(): void {
		$client = (new ClientRepository())->createPasswordGrantClient(
			'Material Grabber',
			config('auth.guards.api.provider'),
			TRUE
		);

		if ($this->command !== NULL) {
			$this->command->warn('Das Client-Secret wird nur dieses eine Mal angezeigt:');
			$this->command->line((string) $client->plainSecret);
		}
	}
}
