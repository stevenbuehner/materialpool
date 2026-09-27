<?php

namespace App\Services\Bundles;

use App\Models\Bundle;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BundlesService {


	const LOCAL_DB_FILENAME = 'database.sqlite';
	const BUNDLE_FILES_DIR  = 'files';
	protected $cachedContainerBundleInfos = NULL;


	public function __construct(private readonly BundlePermissionService $bundlePermissionService) {
		$this->cachedContainerBundleInfos = collect();
	}

	public function updateInstalledBundleInfos() {
		$dbBundles = [];

		foreach ($this->getAllBundles() as $bundleInfo) {

			/** @var Bundle $bundleEntity */
			$bundleEntity = Bundle::firstOrNew(
				[
					'uuid' => $bundleInfo["uuid"]
				]
			);

			if ($bundleEntity->exists === FALSE) {
				$bundleEntity->is_installed     = FALSE;
				$bundleEntity->update_available = TRUE;
			}

			// Update path and Name etc.
			$bundleEntity->name           = $bundleInfo["name"];
			$bundleEntity->description    = $bundleInfo["description"];
			$bundleEntity->author         = $bundleInfo["author"];
			$bundleEntity->container_root = $bundleInfo["container_root"];

			if (count($bundleInfo['icons']) > 0) {
				$bundleEntity->icon = array_shift($bundleInfo['icons']);
			} else {
				$bundleEntity->icon = NULL;
			}


			$bundleEntity->save();
			$this->bundlePermissionService->ensureFor($bundleEntity);

			$dbBundles[] = $bundleEntity;
		}

		return $dbBundles;
	}

	public function getAllBundles() {

		$bundleDisk = $this->getBundleDisk();

		$dirs    = $bundleDisk->directories();
		$bundles = collect();

		foreach ($dirs as $bundleDir) {

			$dbPath = $bundleDir . DIRECTORY_SEPARATOR . self::LOCAL_DB_FILENAME;

			if (!$bundleDisk->exists($dbPath)) {
				continue;
			}

			$bundles = $bundles->merge($this->loadContainerBundleInfos($bundleDisk, $dbPath));

		}

		return $bundles;

	}


	public function getBundleDisk() {
		$diskName = $this->getBundleDiskName();
		$disk     = Storage::disk($diskName);

		return $disk;
	}

	public function getBundleDiskName() {
		return config('app.disks.bundles');
	}

	protected function loadContainerBundleInfos(FilesystemAdapter $bundleDisk, $dbPath) {

		$containerName = $this->getContainerNameFromDbPath($dbPath);

		// Load from cache
		if ($this->cachedContainerBundleInfos->has($containerName)) {
			$containerBundles = $this->cachedContainerBundleInfos->get($containerName);

			return $containerBundles;
		}

		$containerBundles = collect();

		// Load from DB
		if (!Config::has("database.connections.$containerName")) {
			$dbConfig = [
				'driver'   => 'sqlite',
				'database' => $bundleDisk->path($dbPath),
				'prefix'   => '',
			];

			// Set the temporary configuration
			Config::set("database.connections.$containerName", $dbConfig);
		}

		$dbConnection = DB::connection($containerName);

		$bundles = $dbConnection->select('SELECT * FROM bundle');
		foreach ($bundles as $bundleInfo) {
			$myBundle                    = (array)$bundleInfo;
			$myBundle["id"]              = (int)$myBundle["id"]; // Parse to int
			$myBundle['container_root']  = dirname($dbPath);
			$myBundle['disk_files']      = dirname(($dbPath)) . '/files';
			$myBundle['connection']      = $containerName;
			$myBundle['count_materials'] = (int)$dbConnection->selectOne('SELECT COUNT(*) as Anzahl FROM material WHERE bundle_id=:bundle_id', ['bundle_id' => $bundleInfo->id])->Anzahl;
			$myBundle['count_files']     = (int)$dbConnection->selectOne('SELECT COUNT(mf.file_id) as Anzahl FROM material m INNER JOIN material_files mf ON (m.id = mf.material_id) WHERE m.bundle_id=:bundle_id', ['bundle_id' => $bundleInfo->id])->Anzahl;
			$myBundle['icons']           = isset($bundleInfo->icons) ? json_decode($bundleInfo->icons) : [];

			$containerBundles->put($bundleInfo->uuid, $myBundle);
		}

		$this->cachedContainerBundleInfos->put($containerName, $containerBundles);

		return $containerBundles;
	}

	protected function getContainerNameFromDbPath($dbPath) {
		$containerName = trim(strtolower(dirname($dbPath)));

		return $containerName;
	}

	public function getBundleFiles($bundleInfo, $page = 1, $resourcesPerPage = 100) {

		$connection = $this->getBundleConnection($bundleInfo['connection']);

		$query = 'SELECT DISTINCT files.* FROM material INNER JOIN material_files ON (material.id = material_files.material_id) INNER JOIN files ON (files.id=material_files.file_id) WHERE material.bundle_id=:BUNDLE_ID ';

		// Pagination
		$page             = ($page <= 0) ? 1 : (int)$page;  // Start at 1
		$resourcesPerPage = ($resourcesPerPage <= 0) ? 100 : (int)$resourcesPerPage;
		$start            = ($page - 1) * $resourcesPerPage;
		$limit            = " ORDER BY files.id ASC, files.uuid ASC LIMIT " . $start . "," . $resourcesPerPage;

		$data = $connection->select($query . $limit, ['BUNDLE_ID' => $bundleInfo["id"]]);


		return $data;
	}

	protected function getBundleConnection($connectionName) {
		return DB::connection($connectionName);
	}

	public function getMaterialMetaData(Bundle $bundle, $materialId) {

		$connection = $this->getLocalBundleConnection($bundle);
		$query      = 'SELECT type, value, relevance, custom_icon_path FROM meta_data WHERE material_id=:MATERIAL';
		$data       = $connection->select($query, ['MATERIAL' => $materialId]);


		return $data;
	}

	protected function getLocalBundleConnection(Bundle $bundle) {
		$info = $this->getLocalBundleData($bundle);

		return $this->getBundleConnection($info['connection']);

		return $dbConnection;
	}

	/**
	 * @param Bundle $bundle
	 * @return array|FALSE
	 * @throws FileNotFoundException
	 */
	public function getLocalBundleData(Bundle $bundle) {
		$bundleInfo = $this->getBundles($bundle->container_root, $bundle->uuid);

		return $bundleInfo;
	}

	/**
	 * @param      $rootPath
	 * @param null $filterUuid
	 * @return \Illuminate\Support\Collection|FALSE|array FALSE if $uuid was not Found; array if $uuid was found, Collection for multiple bundles in one file
	 * @throws FileNotFoundException
	 */
	public function getBundles($rootPath, $filterUuid = NULL) {
		$bundleDisk = $this->getBundleDisk();
		$dbPath     = $rootPath . '/' . self::LOCAL_DB_FILENAME;

		if (!$bundleDisk->exists($rootPath)) {
			throw new FileNotFoundException($rootPath);
		} else if (!$bundleDisk->exists($dbPath)) {
			throw new FileNotFoundException($dbPath);
		}

		$multipleBundleInfos = $this->loadContainerBundleInfos($this->getBundleDisk(), $dbPath);


		if ($filterUuid !== NULL) {
			$oneBundleInfo = $multipleBundleInfos->get($filterUuid, FALSE);

			return $oneBundleInfo;
		}

		return $multipleBundleInfos;

	}

	public function getMaterialAssignedFileUUIDs(Bundle $bundle, $materialId) {

		$connection = $this->getLocalBundleConnection($bundle);
		$query      = 'SELECT files.uuid FROM material_files INNER JOIN files ON (material_files.file_id = files.id) WHERE material_id=:MATERIAL';
		$data       = $connection->select($query, ['MATERIAL' => $materialId]);

		return $data;
	}

	public function getFileRootPath(Bundle $bundle, $fileInfo) {
		//	return $bundle->container_root . DIRECTORY_SEPARATOR . self::BUNDLE_FILES_DIR . DIRECTORY_SEPARATOR . $fileInfo->filePath;
	}

	public function getBundleMaterials($bundleInfo, $page = 1, $perPage = 100) {
		$connection = $this->getBundleConnection($bundleInfo['connection']);

		$query = 'SELECT DISTINCT material.* FROM material INNER JOIN material_files ON material.id = material_files.material_id WHERE material.bundle_id=:BUNDLE_ID ';

		// Pagination
		$page    = ($page <= 0) ? 1 : (int)$page;  // Start at 1
		$perPage = ($perPage <= 0) ? 100 : (int)$perPage;
		$start   = ($page - 1) * $perPage;
		$limit   = " ORDER BY material.id ASC, material.uuid ASC LIMIT " . $start . "," . $perPage;

		$data = $connection->select($query . $limit, ['BUNDLE_ID' => $bundleInfo['id']]);

		return $data;
	}

	public function hasMaterial($bundle, $uuid) {

		$connection = $this->getLocalBundleConnection($bundle);

		$query = 'SELECT  material.* FROM material WHERE material.uuid=:UUID ';
		$data  = $connection->select($query, ['UUID' => $uuid]);

		return count($data) > 0;

	}

	public function hasFile($bundle, $uuid) {

		$connection = $this->getLocalBundleConnection($bundle);

		$query = 'SELECT  files.* FROM files WHERE files.uuid=:UUID ';
		$data  = $connection->select($query, ['UUID' => $uuid]);

		return count($data) > 0;
	}

}
