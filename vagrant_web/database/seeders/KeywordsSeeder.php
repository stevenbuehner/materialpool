<?php

namespace Database\Seeders;

use App\Models\Keyword;
use Illuminate\Database\Seeder;

class KeywordsSeeder extends Seeder {

	public function run() {

		Keyword::firstOrCreateLang('Deutsch');
		Keyword::firstOrCreateLang('Englisch');
		Keyword::firstOrCreateLang('Französisch');

		Keyword::firstOrCreatePerson('Steven Bühner');
		Keyword::firstOrCreatePerson('Marit Bühner');
		Keyword::firstOrCreatePerson('Friedhelm Bühner');

		Keyword::firstOrCreate(['title' => 'für Kinder']);
		Keyword::firstOrCreate(['title' => 'für Teens']);
		Keyword::firstOrCreate(['title' => 'für Jugendliche']);
		Keyword::firstOrCreate(['title' => 'für Hauskreise']);
		Keyword::firstOrCreate(['title' => 'für Familien']);

		Keyword::firstOrCreatePlace('Schönaich');

	}
}
