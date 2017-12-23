<?php

use Illuminate\Database\Seeder;

class ApiKeysSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {

		$repo   = new \Laravel\Passport\ClientRepository();
		$client = $repo->createPasswordGrantClient(NULL, 'Material Grabber', 'http://localhost');
		$client->forceFill(['secret' => 'BsbBi5TMALcnnJZmsQUo7P2brXdLteRB94ExREar'])->save();

	}
}
