<?php

namespace App\Services\Bundles;

use App\Exceptions\Bundles\BundleSourceValidationException;
use App\Models\Bundle;
use App\Models\Keyword;
use App\Models\Material;
use App\Services\ResourceRecognition\ResourceRecognitionService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class BundleSourceValidator {
	private const REQUIRED_COLUMNS = [
		'bundle' => ['id', 'uuid', 'name', 'version', 'author', 'description', 'icons'],
		'material' => ['id', 'bundle_id', 'uuid', 'material_created', 'material_modified', 'title', 'description', 'author_name', 'author_rating', 'from_bot'],
		'files' => ['id', 'uuid', 'file_created', 'file_modified', 'notes', 'is_public', 'file_path', 'public_path', 'original_basename', 'mime_type'],
		'material_files' => ['material_id', 'file_id'],
		'meta_data' => ['material_id', 'type', 'value', 'relevance', 'custom_icon_path'],
	];

	public function __construct(
		private BundlesService $bundlesService,
		private ResourceRecognitionService $resourceRecognitionService,
	) {
	}

	/**
	 * @return array{bundle_info: array, source_fingerprint: string}
	 */
	public function validate(Bundle $bundle): array {
		$bundleInfo = $this->bundlesService->getLocalBundleData($bundle);
		if ($bundleInfo === FALSE || empty($bundleInfo['uuid']) || $bundleInfo['uuid'] !== $bundle->uuid || empty($bundleInfo['version'])) {
			throw new BundleSourceValidationException('bundle_source_identity_invalid');
		}

		$connection = DB::connection($bundleInfo['connection']);
		$this->assertSchema($connection);
		$this->assertIdentifiers($connection, $bundleInfo['id']);
		$this->assertMaterialDataIsValid($connection, $bundleInfo['id']);
		$this->assertReferencedFilesAreValid($bundle, $connection, $bundleInfo['id']);

		$databasePath = $bundle->container_root . '/' . BundlesService::LOCAL_DB_FILENAME;
		$sourceFingerprint = hash_file('sha256', $this->bundlesService->getBundleDisk()->path($databasePath));
		if ($sourceFingerprint === FALSE) {
			throw new BundleSourceValidationException('bundle_source_fingerprint_failed');
		}

		return ['bundle_info' => $bundleInfo, 'source_fingerprint' => $sourceFingerprint, 'warnings' => ['material_ids' => [], 'file_uuids' => []]];
	}

	private function assertSchema(ConnectionInterface $connection): void {
		$schema = $connection->getSchemaBuilder();
		foreach (self::REQUIRED_COLUMNS as $table => $columns) {
			if (!$schema->hasTable($table) || !$schema->hasColumns($table, $columns)) {
				throw new BundleSourceValidationException('bundle_source_schema_invalid');
			}
		}
	}

	private function assertIdentifiers(ConnectionInterface $connection, int $sourceBundleId): void {
		if ($this->hasEmptyOrDuplicateUuid($connection, 'material', $sourceBundleId)
			|| $this->hasEmptyOrDuplicateUuid($connection, 'files', $sourceBundleId)) {
			throw new BundleSourceValidationException('bundle_source_uuid_invalid');
		}
	}

	private function hasEmptyOrDuplicateUuid(ConnectionInterface $connection, string $table, int $sourceBundleId): bool {
		if ($table === 'material') {
			$rows = $connection->select('SELECT uuid FROM material WHERE bundle_id=:bundle_id GROUP BY uuid HAVING uuid IS NULL OR TRIM(uuid) = \'\' OR COUNT(*) > 1', ['bundle_id' => $sourceBundleId]);

			return count($rows) > 0;
		}

		$rows = $connection->select('SELECT files.uuid FROM files INNER JOIN material_files ON material_files.file_id=files.id INNER JOIN material ON material.id=material_files.material_id WHERE material.bundle_id=:bundle_id GROUP BY files.uuid HAVING files.uuid IS NULL OR TRIM(files.uuid) = \'\' OR COUNT(DISTINCT files.id) > 1', ['bundle_id' => $sourceBundleId]);

		return count($rows) > 0;
	}

	private function assertMaterialDataIsValid(ConnectionInterface $connection, int $sourceBundleId): void {
		$invalidIds = $connection->select('SELECT id FROM material WHERE bundle_id=:bundle_id AND author_rating IS NOT NULL AND (author_rating < 0 OR author_rating > :max_rating)', ['bundle_id' => $sourceBundleId, 'max_rating' => Material::MAX_RATING]);
		$metadata = $connection->select('SELECT material.id AS material_id, meta_data.type, meta_data.value FROM meta_data INNER JOIN material ON material.id=meta_data.material_id WHERE material.bundle_id=:bundle_id', ['bundle_id' => $sourceBundleId]);
		foreach ($metadata as $metadataEntry) {
			if (in_array($metadataEntry->type, array_keys(Keyword::AVAILABLE_TYPES), TRUE)) {
				continue;
			}
			if ($metadataEntry->type === 'bibleverse' && preg_match('~^(\d+):(\d+):(\d+)\-(\d+):(\d+):(\d+)$~', $metadataEntry->value) === 1) {
				continue;
			}

			$invalidIds[] = (object)['id' => $metadataEntry->material_id];
		}

		if ($invalidIds !== []) {
			throw new BundleSourceValidationException('bundle_source_material_invalid');
		}
	}

	private function assertReferencedFilesAreValid(Bundle $bundle, ConnectionInterface $connection, int $sourceBundleId): void {
		$files = $connection->select('SELECT DISTINCT files.uuid, files.file_path, files.mime_type FROM files INNER JOIN material_files ON material_files.file_id=files.id INNER JOIN material ON material.id=material_files.material_id WHERE material.bundle_id=:bundle_id', ['bundle_id' => $sourceBundleId]);
		$disk = $this->bundlesService->getBundleDisk();

		foreach ($files as $file) {
			if (!$this->isSafeRelativePath($file->file_path)) {
				throw new BundleSourceValidationException('bundle_source_resource_invalid');
			}
			if (!$disk->exists($bundle->container_root . '/' . BundlesService::BUNDLE_FILES_DIR . '/' . $file->file_path)) {
				throw new BundleSourceValidationException('bundle_source_resource_invalid');
			}
			if (!class_exists($this->resourceRecognitionService->guessResourceFileFromMimeType($file->mime_type))) {
				throw new BundleSourceValidationException('bundle_source_resource_invalid');
			}
		}
	}

	private function isSafeRelativePath(string $path): bool {
		$normalizedPath = str_replace('\\', '/', $path);
		if ($normalizedPath === '' || str_starts_with($normalizedPath, '/') || preg_match('~^[a-zA-Z]:/~', $normalizedPath) === 1) {
			return FALSE;
		}

		return !collect(explode('/', $normalizedPath))->contains(fn(string $segment) => $segment === '' || $segment === '.' || $segment === '..');
	}
}
