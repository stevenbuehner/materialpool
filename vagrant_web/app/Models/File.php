<?php

namespace App\Models;

use App\Services\TagExtraction\ResourceHandles\FileExifHandler;
use App\Services\TagExtraction\ResourceHandles\FileNameHandler;
use App\Services\TagExtraction\ResourceHandles\HandlerInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Parental\HasChildren;

/**
 * Class File
 *
 * @package App\Models
 * @property string|null $original_filename
 * @property int $filesize
 */
class File extends Resource {
	use HasFactory;
	use HasChildren;

	protected static $singleTableType       = 'file';
	protected static $ORIGINAL_FILENAME     = 'of';
	protected $childTypes = [
		'file'  => File::class,
		'audio' => AudioFile::class,
		'video' => VideoFile::class,
		'image' => ImageFile::class,
		'doc'   => DocumentFile::class,
		'pdf'   => PdfFile::class,
	];

	protected $cachedData = [];

	protected static function booted(): void {
		parent::booted();

		static::addGlobalScope('file_types', function ($query): void {
			$query->whereIn($query->getModel()->getTable().'.type', array_keys((new static())->getChildTypes()));
		});
	}

	public function __construct(array $attributes = []) {
		parent::__construct($attributes);

		// Add Attribute
		$this->appends[]  = 'original_filename';
		$this->fillable[] = 'original_filename';

		// Nicht automatisch bei JSON-Ausgabe hinzufügen
		$this->appends[] = 'filesize';

		$this->additionalEditViews[] = 'resources.files.edit-partial';
	}

	public static function getValidationRules() {
		$rules                      = parent::getValidationRules();
		$rules['file']              = 'bail|required|file';
		$rules['original_filename'] = 'string';

		return $rules;
	}

	/**
	 * @return HandlerInterface[]
	 */
	public function getTagExtractionClasses() {
		return [
			FileNameHandler::class,
			FileExifHandler::class
		];
	}

	public function setOriginalFilenameAttribute($originalFileName) {
		$this->setOption(self::$ORIGINAL_FILENAME, $originalFileName);
	}


	public function getOriginalFilenameAttribute() {
		return $this->getOption(self::$ORIGINAL_FILENAME, NULL);
	}

	/**
	 * @param $filesize
	 * @throws \Exception
	 */
	public function setFilesizeAttribute($filesize) {
		throw  new \Exception('Filesize can not be set');
	}

	public function getFilesizeAttribute() {
		$filesize = 0;

		if ($this->hasLocalFile()) {
			try {
				$filesize = $this->getLocalDisk()->size($this->getLocalFilePath());
			} catch (FileNotFoundException $e) {
			}
		}

		return $filesize;
	}

	public function setLocalStorageAndPath($storageName, $path) {
		$this->setLocalPathAttribute($storageName . '::' . $path);
	}

	public function setLocalPathAttribute($path) {
		// Possible to override and add extra actions
		$this->attributes['local_path'] = $path;
		$this->clearDataCache('storage');
		$this->clearDataCache('path');
	}

	protected function clearDataCache($key) {
		if (isset($this->cachedData[$key])) {
			unset($this->cachedData[$key]);
		}
	}

	public function setRemotePathAttribute($path) {
		$this->attributes['remote_path'] = $path;
	}

	public function getLocalFile() {
		return $this->getLocalDisk()->get($this->getLocalFilePath());
	}

	/**
	 * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
	 */
	public function getLocalDisk() {
		list($storage, $path) = $this->getLocalStorageAndPath();

		try {
			return Storage::disk($storage);
		} catch (\InvalidArgumentException $e) {
			// Wenn der gegebene $storage-String nicht existiert
			Log::error('Given Storage-Name in DB does not exist.', ['id' => $this->id]);
			throw $e;
		}

	}

	public function getLocalStorageAndPath() {
		if (!isset($this->cachedData['storage']) || !isset($this->cachedData['path'])) {
			$local = $this->getAttribute('local_path');

			$split = preg_split('~::~', $local, 2);

			if (count($split) === 2) {
				list($storage, $path) = $split;
			} else {
				$storage = NULL;
				$path    = $split[0];
			}

			$this->setDataCache('storage', $storage);
			$this->setDataCache('path', $path);
		}

		return [
			$this->cachedData['storage'],
			$this->cachedData['path']
		];
	}

	protected function setDataCache($key, $value) {
		$this->cachedData[$key] = $value;
	}

	public function getLocalFilePath() {
		list($storage, $path) = $this->getLocalStorageAndPath();

		return $path;
	}

	/**
	 * @return false|resource
	 * @throws FileNotFoundException
	 */
	public function getLocalFileStream() {
		return $this->getLocalDisk()->readStream($this->getLocalFilePath());
	}

	public function deleteLocalFile() {
		try {
			$result = $this->getLocalDisk()->delete($this->getLocalFilePath());
		} catch (FileNotFoundException $e) {
			$result = FALSE;
		}

		$this->setAttribute('local_path', NULL);
		$this->setAttribute('original_filename', '');

		return $result;
	}

	public function getLocalMimeType() {
		return $this->getLocalDisk()->mimeType($this->getLocalFilePath());
	}

	public function getLocalLastModified() {
		return $this->getLocalDisk()->lastModified($this->getLocalFilePath());
	}

	public function getLocalUrl() {
		return $this->getLocalDisk()->url($this->getLocalFilePath());
	}

	/**
	 * @return \Illuminate\Filesystem\FilesystemAdapter
	 */
	public function getLocalSize() {
		return $this->getLocalDisk()->size($this->getLocalFilePath());
	}

	/**
	 * Returns the absolute SYSTEM-File-Path
	 *
	 * @return FALSE|string
	 */
	public function getAbsoluteLocalPath() {
		list($storage, $path) = $this->getLocalStorageAndPath();

		if (config("filesystems.disks.$storage.driver") === 'local') {
			return $this->getLocalDisk()->path($path);
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasLocalFile() {
		return !empty($this->getAttribute('local_path'));
	}

	public function localFileExists() {
		$disk = $this->getLocalDisk();
		$path = $this->getLocalFilePath();

		return $disk->exists($path);
	}

	/**
	 * @return bool
	 */
	public function hasRemoteFile() {
		return !empty($this->getAttribute('remote_path'));
	}

	public function getRemoteFileStream() {
		// Todo: Never tested so far!
		if (strpos($this->remote_path, 'http') == 0) {
			// Http-Request
			$stream = fopen($this->remote_path, 'r');

			return $stream;
		}

		return FALSE;
	}


}
