<?php

namespace Tests\Feature;

use Database\Seeders\ImportBibleverseCrossReferences;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BibleverseCrossReferenceSeederTest extends TestCase
{
    use DatabaseMigrations;

    public function test_migration_creates_empty_table_and_explicit_seed_imports_complete_data(): void
    {
        $this->assertSame(0, DB::table('bibleverses_cross_ref')->count());

        $this->seed(FixtureCrossReferenceSeeder::class);

        $this->assertSame(3, DB::table('bibleverses_cross_ref')->count());
        $this->assertDatabaseHas('bibleverses_cross_ref', [
            'source' => 1001001,
            'relevance' => 12,
            'target_from' => 19089011,
            'target_to' => 19089012,
        ]);
        $this->assertDatabaseHas('bibleverses_cross_ref', [
            'source' => 66022021,
            'relevance' => 4,
            'target_from' => 53003018,
            'target_to' => 0,
        ]);

        DB::table('bibleverses_cross_ref')->insert([
            'source' => 1001002,
            'relevance' => 1,
            'target_from' => 1001003,
            'target_to' => 0,
        ]);

        $this->seed(FixtureCrossReferenceSeeder::class);

        $this->assertSame(3, DB::table('bibleverses_cross_ref')->count());
        $this->assertDatabaseMissing('bibleverses_cross_ref', ['source' => 1001002]);
    }
}

class FixtureCrossReferenceSeeder extends ImportBibleverseCrossReferences
{
    protected function sourcePath(): string
    {
        return __DIR__.'/../Fixtures/cross-reference-sample.sql';
    }
}
