<?php

namespace Tests\Feature\Bundles;

use App\Models\Bundle;
use App\Services\Bundles\BundlesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\TestCase;

class BundlesServiceSourceQueryTest extends TestCase {
	use RefreshDatabase;

	public function test_bundle_queries_filter_the_requested_uuid_and_paginate_in_stable_order(): void {
		$bundle = $this->createBundleSource();
		$service = resolve(BundlesService::class);
		$bundleInfo = $service->getLocalBundleData($bundle);

		$this->assertSame($bundle->uuid, $bundleInfo['uuid']);
		$this->assertSame([10], collect($service->getBundleFiles($bundleInfo, 1, 1))->pluck('id')->all());
		$this->assertSame([20], collect($service->getBundleFiles($bundleInfo, 2, 1))->pluck('id')->all());
		$this->assertSame([1], collect($service->getBundleMaterials($bundleInfo, 1, 1))->pluck('id')->all());
		$this->assertSame([2], collect($service->getBundleMaterials($bundleInfo, 2, 1))->pluck('id')->all());
	}

	private function createBundleSource(): Bundle {
		Storage::fake('bundles');
		$bundle = Bundle::query()->create([
			'name' => 'Source query fixture',
			'uuid' => 'source-query-fixture',
			'container_root' => 'source-query-fixture',
		]);
		$disk = Storage::disk('bundles');
		$disk->makeDirectory('source-query-fixture');
		$databasePath = $disk->path('source-query-fixture/database.sqlite');
		$pdo = new PDO('sqlite:' . $databasePath);

		$pdo->exec('CREATE TABLE bundle (id INTEGER PRIMARY KEY, icons TEXT NOT NULL, uuid TEXT NOT NULL, name TEXT NOT NULL, version TEXT NOT NULL, author TEXT, description TEXT, exportDate TEXT NOT NULL)');
		$pdo->exec('CREATE TABLE material (id INTEGER PRIMARY KEY, bundle_id INTEGER, material_modified TEXT NOT NULL, title TEXT NOT NULL, description TEXT, material_created TEXT NOT NULL, author_name TEXT, author_rating INTEGER, from_bot INTEGER NOT NULL, uuid TEXT NOT NULL)');
		$pdo->exec('CREATE TABLE files (id INTEGER PRIMARY KEY, uuid TEXT NOT NULL, file_created TEXT NOT NULL, file_modified TEXT NOT NULL, metadata_modified TEXT NOT NULL, notes TEXT, is_public INTEGER NOT NULL, file_path TEXT NOT NULL, public_path TEXT, original_basename TEXT, author_name TEXT, mime_type TEXT NOT NULL)');
		$pdo->exec('CREATE TABLE material_files (material_id INTEGER NOT NULL, file_id INTEGER NOT NULL)');
		$pdo->exec("INSERT INTO bundle VALUES (1, '[]', 'source-query-fixture', 'Fixture', '1.0.0', NULL, NULL, '2026-09-18')");
		$pdo->exec("INSERT INTO material VALUES (2, 1, '2026-09-18', 'Second', NULL, '2026-09-18', NULL, NULL, 1, 'material-2')");
		$pdo->exec("INSERT INTO material VALUES (1, 1, '2026-09-18', 'First', NULL, '2026-09-18', NULL, NULL, 1, 'material-1')");
		$pdo->exec("INSERT INTO files VALUES (20, 'file-20', '2026-09-18', '2026-09-18', '2026-09-18', NULL, 1, 'second.pdf', NULL, NULL, NULL, 'application/pdf')");
		$pdo->exec("INSERT INTO files VALUES (10, 'file-10', '2026-09-18', '2026-09-18', '2026-09-18', NULL, 1, 'first.pdf', NULL, NULL, NULL, 'application/pdf')");
		$pdo->exec('INSERT INTO material_files VALUES (1, 10)');
		$pdo->exec('INSERT INTO material_files VALUES (2, 20)');

		return $bundle;
	}
}
