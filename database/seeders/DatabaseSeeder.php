<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		// $this->call(UsersTableSeeder::class);
		$this->call(ClearAllTablesSeeder::class);
		$this->call(ResourceSeeder::class);
		$this->call(ApiKeysSeeder::class);
		$this->call(KeywordsSeeder::class);

		// Das braucht jedes Mal ziemlich lang -> beim testen möchte ich das nicht jedes Mal drin haben
		// $this->call(ImportBibleContent::class);
	}
}
