<?php

namespace App\Services\Bundles;

use App\Models\Bundle;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class BundlesService {


	const LOCAL_DB_FILENAME = 'database.sqlite';
	const BUNDLE_FILES_DIR  = 'files';
	protected $existingBundleInfo = NULL;

	public function updateInstalledBundleInfos() {
		foreach ($this->getAllBundles() as $bundleInfo) {

			$bundle = Bundle::firstOrCreate(
				[
					'uuid' => $bundleInfo["uuid"]
				],
				[
					'name'             => $bundleInfo["name"],
					'description'      => $bundleInfo["description"],
					'author'           => $bundleInfo["author"],
					'container_root'   => $bundleInfo["container_root"],
					'is_installed'     => FALSE,
					'update_available' => TRUE
				]
			);
		}
	}

	public function getAllBundles() {

		$bundleDisk = $this->getBundleDisk();

		$dirs    = $bundleDisk->allDirectories();
		$bundles = collect();

		foreach ($dirs as $bundleDir) {

			$dbPath = $bundleDir . DIRECTORY_SEPARATOR . self::LOCAL_DB_FILENAME;

			if (!$bundleDisk->exists($dbPath)) {
				continue;
			}

			$bundles = $bundles->merge($this->loadBundleInfo($bundleDisk, $dbPath));

		}

		return $bundles;

	}


	protected function getBundleDisk() {
		$diskName = $this->getBundleDiskName();
		$disk     = Storage::disk($diskName);

		return $disk;
	}

	public function getBundleDiskName() {
		return config('app.disks.bundles');
	}

	protected function loadBundleInfo(FilesystemAdapter $bundleDisk, $dbPath) {

		if ($this->existingBundleInfo === NULL) {
			$bundleInfos   = collect();
			$containerName = $this->getContainerNameFromDbPath($dbPath);

			if (!Config::has("database.connections.$containerName")) {
				/** @var Filesystem $driver */
				$driver = $bundleDisk->getDriver();

				/** @var Local $adapter */
				$adapter = $driver->getAdapter();

				$localPrefix = $adapter->getPathPrefix();


				$dbConfig = [
					'driver'   => 'sqlite',
					'database' => $localPrefix . $dbPath,
					'prefix'   => '',
				];

				// Set the temporary configuration
				Config::set("database.connections.$containerName", $dbConfig);
			}

			$dbConnection = DB::connection($containerName);

			$bundles = $dbConnection->select('SELECT * FROM bundle');
			foreach ($bundles as $bundleInfo) {
				$myBundle                    = (array) $bundleInfo;
				$myBundle['container_root']  = dirname($dbPath);
				$myBundle['disk_files']      = dirname(($dbPath)) . '/files';
				$myBundle['connection']      = $containerName;
				$myBundle['count_materials'] = (int) $dbConnection->selectOne('SELECT COUNT(*) as Anzahl FROM material WHERE bundle_id=' . $bundleInfo->id)->Anzahl;
				$myBundle['count_files']     = (int) $dbConnection->selectOne('SELECT COUNT(mf.file_id) as Anzahl FROM material m INNER JOIN material_files mf ON (m.id = mf.material_id) WHERE m.bundle_id=' . $bundleInfo->id)->Anzahl;

				$bundleInfos->put($bundleInfo->uuid, $myBundle);
			}

			$this->existingBundleInfo = $bundleInfos;
		}

		return $this->existingBundleInfo;
	}

	protected function getContainerNameFromDbPath($dbPath) {
		$containerName = trim(strtolower(dirname($dbPath)));

		return $containerName;
	}

	public function getBundleFiles(Bundle $bundle, $page = 1, $resourcesPerPage = 100) {

		$connection = $this->getLocalBundleConnection($bundle);

		$query = 'SELECT DISTINCT files.* FROM material INNER JOIN material_files ON (material.id = material_files.material_id) INNER JOIN files ON (files.id=material_files.file_id) WHERE material.bundle_id=:BUNDLE_ID ';

		// Pagination
		$page             = ($page <= 0) ? 1 : (int) $page;  // Start at 1
		$resourcesPerPage = ($resourcesPerPage <= 0) ? 100 : $resourcesPerPage;
		$start            = $start = ($page - 1) * $resourcesPerPage;
		$limit            = " LIMIT " . $start . "," . $resourcesPerPage;

		$data = $connection->select($query . $limit, ['BUNDLE_ID' => $bundle->id]);


		return $data;
	}

	protected function getLocalBundleConnection(Bundle $bundle) {
		$info = $this->getLocalBundleData($bundle);

		$dbConnection = DB::connection($info['connection']);

		return $dbConnection;
	}

	/**
	 * @param Bundle $bundle
	 * @return array|FALSE
	 * @throws FileNotFoundException
	 */
	public function getLocalBundleData(Bundle $bundle) {
		$bundleService = resolve(BundlesService::class);
		$bundleInfo    = $bundleService->getBundles($bundle->container_root, $bundle->uuid);

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

		if (!$bundleDisk->has($rootPath)) {
			throw new FileNotFoundException($rootPath);
		} else if (!$bundleDisk->has($dbPath)) {
			throw new FileNotFoundException($dbPath);
		}

		$multipleBundleInfos = $this->loadBundleInfo($this->getBundleDisk(), $dbPath);


		if (!$filterUuid !== NULL) {
			$oneBundleInfo = $multipleBundleInfos->get($filterUuid, FALSE);

			return $oneBundleInfo;
		}

		return $multipleBundleInfos;

	}

	public function getMaterialMetaData(Bundle $bundle, $materialId) {

		$connection = $this->getLocalBundleConnection($bundle);
		$query      = 'SELECT type, value, relevance, custom_icon_path FROM meta_data WHERE material_id=:MATERIAL';
		$data       = $connection->select($query, ['MATERIAL' => $materialId]);


		return $data;
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

	public function getBundleMaterials($bundle, $page = 1, $perPage = 100) {
		$connection = $this->getLocalBundleConnection($bundle);

		$query = 'SELECT DISTINCT material.* FROM material WHERE bundle_id=:BUNDLE_ID ';

		// Pagination
		$page    = ($page <= 0) ? 1 : (int) $page;  // Start at 1
		$perPage = ($perPage <= 0) ? 100 : $perPage;
		$start   = $start = ($page - 1) * $perPage;
		$limit   = " LIMIT " . $start . "," . $perPage;

		$data = $connection->select($query . $limit, ['BUNDLE_ID' => $bundle->id]);

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