<?php

use Illuminate\Database\Seeder;

class ClearAllTablesSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {

		DB::table('keywords')->delete();
		DB::table('keyword_material')->delete();
		DB::table('materials')->delete();
		DB::table('material_resource')->delete();
		DB::table('resources')->delete();
		DB::table('users')->delete();
		DB::table('password_resets')->delete();
		DB::table('foreign_instances')->delete();
		DB::table('foreign_resource_keys')->delete();
	}
}
